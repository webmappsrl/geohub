> Ticket: oc:8361

# Fix aggiornamento featured image POI da OSM/Wikimedia Commons

## Cosa cambia
Il comando `geohub:update_pois_from_osm` (e l'importer iniziale dei POI OSM) vengono corretti affinché la featured image dei POI con tag `wikimedia_commons` venga effettivamente aggiornata quando la foto cambia su Wikimedia Commons, con errori visibili invece di fallimenti silenziosi, e con le thumbnails rigenerate di conseguenza.

## Perché
I POI CAI Parma (e potenzialmente altri progetti con lo stesso flusso di sync OSM) mostrano foto obsolete perché:
- il download del file Wikimedia usa `file_get_contents()` con uno User-Agent generico, che Wikimedia può rifiutare (403) senza che l'errore emerga in console
- la geometria del media viene costruita interpolando `$poi->geometry` (rappresentazione binaria PostGIS via Eloquent) direttamente in una stringa SQL, un pattern fragile e diverso da quello usato altrove nel codebase (`GeometryFeatureTrait`, che rilegge sempre la geometria con una query parametrizzata)
- le eccezioni nel blocco di aggiornamento media sono catturate e loggate solo con `Log::info`, quindi il comando termina con messaggio di successo anche se l'immagine non è stata aggiornata
- il check "già aggiornato" confronta solo le date (`updated_at` vs timestamp Wikimedia), non il file effettivo: se OSM cambia il filename Commons ma il record locale ha una data più recente, l'update viene saltato comunque

Caso concreto: POI `102105` (B072;LDP041), tag OSM `wikimedia_commons=File:It-pr-ldpB072v2.jpg`, ma `ec_media` `102071` resta ancorato al vecchio `File:It-pr-ldpB072.jpg`.

## Requisiti
- [x] Il download dell'immagine da Wikimedia Commons usa `Http::withHeaders()` con lo stesso User-Agent già usato per le richieste di metadata, al posto di `file_get_contents()` con User-Agent generico
- [x] La risposta HTTP del download viene validata (`successful()` e body non vuoto) prima di essere salvata su storage
- [x] La geometria del media (sia in creazione sia in aggiornamento) viene costruita con una query sicura e parametrizzata (`WHERE id = ?`), non da interpolazione diretta di `$poi->geometry` in una stringa SQL
- [x] L'aggiornamento della featured image usa un criterio combinato, senza nuove colonne/migration (solo dati già disponibili a runtime):
  - **check primario (filename):** confronto tra `rawurldecode(basename($currentFeatureImage->url))` e `rawurldecode($page['title'])` — se diversi, forza l'update indipendentemente dalle date
  - **check secondario (data, fallback):** se i filename coincidono, mantiene il confronto esistente sulle date (`updated_at` vs timestamp Wikimedia) per coprire il caso raro di stesso filename con contenuto ricaricato
- [x] Quando la featured image di un `ec_media` **esistente** viene aggiornata, viene ridispacciata la stessa catena di enrichment (`updateDataChain()` / job `UpdateEcMedia`) oggi dispacciata solo alla creazione (`static::created`), così le thumbnails vengono rigenerate e non restano quelle della vecchia immagine
- [x] Gli errori nel blocco di aggiornamento media vengono resi visibili in console (`$this->error`) e il POI viene aggiunto a `$errorPois`, oltre al log esistente
- [x] Lo stesso allineamento dello User-Agent viene applicato anche in `app/Classes/OutSourceImporter/OutSourceImporterFeatureOSMPoi.php::prepareMediaTagsJson()` (import iniziale dei POI OSM)
- [x] Il comando `geohub:update_pois_from_osm` supporta un flag `--dry-run`: applica il criterio di confronto (filename normalizzato + fallback data) e stampa, per ogni POI segnalato come "da aggiornare", il filename attualmente salvato e quello nuovo atteso — senza scaricare né salvare nulla, e senza invocare `generatePoisJson()` (che oggi viene chiamato incondizionatamente a fine comando e pubblicherebbe comunque un export JSON pubblico aggiornato, vanificando l'isolamento del dry-run)
- [x] `$currentFeatureImage->description` NON viene azzerata incondizionatamente sul ramo di update: viene sovrascritta solo se già vuota, altrimenti una descrizione manuale esistente viene preservata
- [x] Il blocco di aggiornamento attributi/nome/geometria del POI (`updatePoiAttribute`, `updatePoiName`, `updatePoiGeometry`, oggi senza protezione) viene racchiuso in un try/catch esplicito: se `$osmPoi['properties']` è assente/null (es. nodo OSM cancellato o reso privato), l'errore viene reso visibile in console e il POI aggiunto a `$errorPois`, senza interrompere il loop sui restanti POI del batch
- [x] Test automatici che coprono: fix del download (mock/verifica header), force-update su cambio filename con data locale più recente, skip corretto quando l'immagine è già aggiornata, dispatch della catena di enrichment (`Queue::fake()`) sul ramo di update di un media esistente, comportamento confermato della geometria EXIF (test senza EXIF GPS → geometria resta quella del POI; test con EXIF GPS via fixture → geometria sovrascritta, non una regressione), regressione su POI senza `wikimedia_commons`, preservazione di `description` esistente sul ramo di update, gestione di un POI con `properties` OSM assente senza interrompere il batch, confronto filename con titolo Commons non-ASCII (es. con accenti/spazi codificati) per verificare che converga in un solo run e non resti "diverso" indefinitamente

## Rischi
- Il job di enrichment (`UpdateEcMedia`) oggi parte solo su `EcMedia::created`, mai su update: estenderlo anche al ramo di update rende solo coerenti i due rami (la logica non cambia), ma il job può sovrascrivere la geometria del media con le coordinate EXIF se il file scaricato le contiene — comportamento **esistente e intenzionale** (serve alla relazione taxonomy "where"), non introdotto da questo fix, quindi non va prevenuto ma **confermato con test espliciti**: un test senza EXIF GPS (la geometria resta quella impostata dalla query sicura sul punto del POI) e un test con EXIF GPS via fixture dedicata (la geometria viene sovrascritta, comportamento atteso non una regressione); poiché il job è dispacciato in coda (`Bus::chain(...)->dispatch()`), i test useranno `Queue::fake()` per verificare il dispatch sul ramo di update
- Il confronto per filename normalizzato può comunque risultare "diverso" per media legacy creati con uno schema di naming differente (es. `OutSourceImporterFeatureOSMPoi.php` salva i file come `sha1($basename).estensione`, non come `$page['title']`) — l'effetto è un singolo re-download/re-save non necessario ma innocuo al primo run post-fix, dopo il quale il filename si riallinea allo schema `$page['title']` e i run successivi restano stabili; mitigato verificando manualmente il caso concreto POI 102105 prima del merge
- Il comando gira schedulato giornaliero per due utenti noti (`caiparma@webmapp.it` alle 20:15 — 2579 POI con `osmid`, 1171 con featured image esistente; `caipontedera@webmapp.it` alle 18:15 — 111 POI con `osmid`, 101 con featured image esistente): un difetto nel nuovo criterio "force update" potrebbe scatenare download/rigenerazioni inattese su queste centinaia di media al primo run post-fix. Mitigato con un runbook basato sul flag `--dry-run` — **nota: anche il dry-run non è a costo zero**, perché deve comunque interrogare l'API metadata Wikimedia per ogni POI con `wikimedia_commons` (fino a ~1272 richieste), lo stesso tipo di traffico che si teme possa causare rate-limiting; va quindi eseguito con giudizio (non ripetutamente/a raffica), non trattato come un'operazione "gratuita":
  1. Prima del deploy, eseguire `--dry-run` su entrambi gli utenti e ispezionare quanti POI vengono segnalati
  2. Se pochi (i soli realmente cambiati su Commons) → procedere con il run reale, anche via cron
  3. Se molti → controllare a campione il filename "vecchio" segnalato: se segue il pattern legacy `sha1(...)` (media creati tramite `OutSourceImporterFeatureOSMPoi.php`, non tramite questo comando) è il caso noto e innocuo descritto sotto — procedere ma con un run **manuale presidiato** (non lasciare al cron non sorvegliato), monitorando eventuali errori/rate-limit di Wikimedia su una raffica di richieste ravvicinate
  4. Se i filename segnalati non seguono nessun pattern riconoscibile o il confronto sembra "rotto" anche su casi ovvi → bloccare il merge e correggere il criterio prima di procedere
- **Rischio residuo, non risolto da questo fix:** il job `UpdateEcMedia::enrichJob()` (dispacciato dopo il salvataggio per generare thumbnails ed EXIF) esegue un **secondo** download interno dell'immagine appena scritta su storage, senza header custom e senza validazione della risposta. Se questo secondo download fallisce, l'errore resta solo nel log (`Log::error` nel `catch()` della chain), non visibile in console né in `$errorPois` — il requisito "errori visibili" di questo ticket copre solo il download sincrono nel comando, non questo secondo download asincrono nel job. Accettato come limite noto, fuori scope per questo ciclo.

## Out of scope
- Bug analogo sulle tracce OSM (`UpdateTrackFromOsm.php`: la catena post-sync DEM/AWS/Elastic viene ricostruita dentro il loop e parte solo per l'ultima traccia aggiornata con successo) — gestito con un ticket Orchestrator separato, da creare a fine workflow solo dopo conferma esplicita finale dell'utente
- Supporto al tag OSM `image` (URL diretto Commons) come fonte alternativa a `wikimedia_commons` — non richiesto dal ticket
- Persistenza dello sha1 fornito da Wikimedia per il confronto file — richiederebbe una migration sulla tabella `ec_media`, esplicitamente escluso su richiesta del dev; si usa il confronto per filename

## Moduli toccati
- `app/Console/Commands/UpdatePOIFromOsm.php` (repo principale `geohub`) — fix principale: download con header corretti, geometria via query sicura, force-update su cambio filename, rigenerazione thumbnails sull'update, errori visibili
- `app/Classes/OutSourceImporter/OutSourceImporterFeatureOSMPoi.php` (repo principale `geohub`) — allineamento User-Agent nel metodo `prepareMediaTagsJson()`
- `config/geohub.php` (repo principale `geohub`) — nuova chiave `wikimedia_user_agent`, condivisa dai due file sopra
- `tests/Feature/UpdatePOIFromOsmTest.php` (repo principale `geohub`) — nuovi test per i casi del test plan
- `tests/Feature/OutSourceImporter/OutSourceImporterFeatureOSMPoi/PrepareMediaTagsJsonTest.php` (repo principale `geohub`, nuovo file) — test dedicato per l'allineamento User-Agent nell'importer iniziale
