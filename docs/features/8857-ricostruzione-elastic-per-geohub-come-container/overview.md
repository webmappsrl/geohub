> Ticket: oc:8857

# Ricostruzione Elastic per Geohub come container sulla macchina di Geohub

## Cosa cambia

Il nodo Elasticsearch usato da Geohub e il servizio `elastic-passthrough` che gli sta davanti
tornano a funzionare, questa volta come container sulla macchina che ospita Geohub invece che su
una macchina dedicata. Dal punto di vista di chi usa il sistema non cambia nulla: stesso nome
pubblico `elastic-json.webmapp.it`, stesse route `/v2/search` e `/track`, stesso formato delle
risposte. Le app mobili già pubblicate e la webapp continuano a funzionare senza essere aggiornate.

Cambia invece ciò che non si vede: il nodo Elastic non è più raggiungibile da internet, perché il
passthrough lo raggiunge attraverso la rete Docker e la porta 9200 non viene pubblicata.

I due container vivono in uno stack Docker a sé, affiancato all'applicazione: Geohub in produzione
non è dockerizzato — gira su Apache con il PHP di sistema, come registrato in
`docs/knowledge/php2shell-sicurezza-server-geohub.md` — e il `docker-compose.yml` presente in
questo repo è solo l'ambiente di sviluppo locale. Il terminatore TLS è l'Apache già in esercizio,
con un vhost in più per `elastic-json.webmapp.it`.

Gli indici, persi insieme al volume, vengono ricostruiti dal database con il comando di
indicizzazione già esistente. La logica di indicizzazione di Geohub non viene modificata in alcun
modo.

## Perché

Il server che ospitava Elasticsearch è stato eliminato e il provider non permette di
ripristinarlo. Finché manca, nelle app e nella webapp restano ferme la lista tracce della home, la
ricerca testuale, i filtri per tema/attività/layer e l'elenco delle tracce nel viewport della
mappa: tutte funzioni che `wm-core` alimenta solo da quell'endpoint, senza alcun ripiego su altre
API.

Rifare una macchina dedicata significherebbe pagare e mantenere un server per due container che
stanno comodamente accanto a Geohub. Il nodo Elastic, inoltre, era già containerizzato sulla
macchina persa: spostare quello stack non è un cambio di architettura, è un trasloco.

La strada alternativa — sostituire il passthrough con un endpoint dentro Geohub, come fa già
Maphub — è stata scartata per questo ciclo: cambierebbe l'URL chiamato dai client e richiederebbe
di rilasciare tutte le app mobili, senza retrocompatibilità per quelle già installate.

## Requisiti

- [ ] Sulla macchina di Geohub gira un container Elasticsearch con un volume persistente per i
      dati, in configurazione a nodo singolo.
- [ ] La porta 9200 non è pubblicata: il nodo è raggiungibile solo dai container della stessa rete
      Docker.
- [ ] Sulla stessa macchina gira il container `elastic-passthrough`, dal branch `develop` del suo
      repo, che espone le route `/v2/search`, `/search` e `/track` e raggiunge Elastic per nome di
      servizio.
- [ ] Il passthrough dispone delle credenziali per autenticarsi verso Elastic, ricreate sul nodo
      nuovo e fornite al container tramite variabile d'ambiente, mai scritte nel repo.
- [ ] `elastic-json.webmapp.it` risponde in HTTPS sulla nuova macchina, con il certificato servito
      dal reverse proxy già presente, e il record DNS punta lì.
- [ ] Geohub scrive sul nodo nuovo: le chiavi di configurazione della connessione
      (`ELASTIC_HOST`, `ELASTIC_USERNAME`, `ELASTIC_PASSWORD`, `ELASTIC_PREFIX`) sono aggiornate
      nell'ambiente di produzione.
- [ ] La ricostruzione degli indici parte da **una sola app pilota** (CAI Parma): si verifica che
      la ricerca torni a funzionare per quella, prima di procedere con le altre.
- [ ] Gli indici `geohub_app_<id>` sono poi ricostruiti per tutte le app con una esecuzione una
      tantum di `php artisan geohub:index-tracks --info-elastic`, con il queue worker attivo fino
      a svuotamento della coda. Il flag `--info-elastic` è obbligatorio: senza di esso il comando
      entra in `buildAllRoutine()` e riscrive anche `config.json` e il geojson dei POI di tutte le
      app, senza conservare i file precedenti.
- [ ] La webapp torna a mostrare lista tracce, ricerca testuale e filtri funzionanti; una app
      mobile già pubblicata, non aggiornata, torna a funzionare senza interventi.
- [ ] Lo schedule e la logica di indicizzazione di Geohub restano identici a prima.
- [ ] L'immagine di Elasticsearch è fissata a una versione 7.x precisa, mai `latest`: il client PHP
      è vincolato a `elasticsearch/elasticsearch: ^7.15` (`composer.json:29`) e un nodo 8.x
      farebbe fallire l'indicizzazione per via del controllo di prodotto del client.
- [ ] La persistenza del volume dei dati è provata con un riavvio del container prima di
      considerare il lavoro concluso: non basta che il volume sia dichiarato.
- [ ] Il passthrough è in ascolto solo sull'interfaccia locale della macchina; l'unico ingresso
      pubblico è il vhost Apache.
- [ ] `ELASTIC_PREFIX` di Geohub e il nome indice atteso dal passthrough coincidono, verificato
      leggendo un documento dall'indice dopo la prima indicizzazione.
- [ ] `ELASTIC_KEY` e `ELASTIC_HTTP` (presenti in `config/services.php:35-36` ma non lette da
      alcun codice) sono ripulite dai valori della macchina dismessa, per non lasciare una
      trappola a chi interverrà dopo.
- [ ] La versione del passthrough messa in produzione è identificabile: `master` allineato a
      `develop` (fast-forward, 10 commit, nessun conflitto) e un tag sul commit da cui è
      costruita l'immagine.
- [ ] Il lavoro è dichiarato concluso su un criterio numerico, non visivo: per ciascun indice il
      conteggio dei documenti è confrontato con le tracce attese, e la coda è vuota senza job
      falliti.

## Rischi

- **Il comando di indicizzazione senza il flag giusto riscrive i dati pubblicati.** Senza opzioni
  entra in `buildAllRoutine()` (`EcTrackIndexCommand.php:72`, `App.php:454-458`), che oltre a
  Elastic riscrive `config.json` e il geojson dei POI di tutte le app, sovrascrivendoli senza
  conservare il file precedente. Mitigazione: si usa sempre
  `--info-elastic`, che esegue solo `elasticRoutine()`.
- **Finestra con indici creati dal mapping dinamico.** Le job di indicizzazione non creano mai
  l'indice col mapping definito: vanno dritte a `$client->index(...)`
  (`TrackElasticIndexTrait.php:42-45`). Se una traccia viene salvata da Nova mentre l'indice non
  esiste, Elasticsearch lo crea deducendo il mapping, quindi senza `name_keyword` di tipo
  `keyword` e senza `max_result_window: 50000`: la ricerca risponde, ma ordina e limita in modo
  diverso, senza alcun errore. Mitigazione: verificata l'app pilota, la ricostruzione delle altre
  parte subito, nella stessa finestra di intervento; ogni app reindicizzata torna al mapping
  corretto perché `--info-elastic` cancella e ricrea l'indice.
- **Riprodurre l'incidente copiando il compose di sviluppo.** Il blocco `elasticsearch` di questo
  repo (`docker-compose.yml:52-68`) non ha volume — i dati vivono nel container e spariscono a un
  `docker compose down` — e pubblica `9200:9200`; le porte pubblicate da Docker, inoltre, non sono
  coperte da una configurazione di firewall di sistema. Mitigazione: lo stack di produzione non
  nasce da quel blocco, e volume e porte sono requisiti verificati.
- **Prefisso disallineato che non dà errore.** Se `ELASTIC_PREFIX` non combacia con il nome indice
  atteso dal passthrough, Geohub indicizza correttamente e il passthrough risponde 200 con zero
  risultati: il sistema sembra sano mentre le app restano vuote. Mitigazione: requisito di
  verifica leggendo un documento reale dall'indice.
- **Errori di connessione che non parlano di Elastic.** `getClient()` non ha un `return` nel ramo
  di errore (`ElasticIndexTrait.php:28-30`), quindi ogni problema di rete si manifesta come
  errore fatale su `null` dentro le job. Non si corregge in questo ciclo, ma va saputo prima: è il
  messaggio che si vedrà se qualcosa non va durante l'intervento.
- **Nessun ritorno indietro.** La macchina di partenza è stata distrutta: non esistono due stati
  validi fra cui scegliere, e lo spostamento del DNS è il punto di non ritorno pratico. Il TTL del
  record va abbassato prima dello spostamento, così un errore di destinazione si corregge in
  minuti invece che in ore.
- **Nessun rimedio se il mapping si rivela sbagliato** se non rifare l'intero reindex: è la
  ragione per cui si parte da una sola app pilota.

Rischi considerati e ritenuti ipotetici, non mitigati in questo ciclo: coda configurata in modo da
eseguire le job inline, che renderebbe il reindex un'unica esecuzione monolitica; compromissione
del nodo Elastic a partire da un container già violato; differenze di comportamento fra il
passthrough su `develop` e la versione realmente in esecuzione sulla macchina persa, che nessuno
può più confrontare.

## Out of scope

- Sostituire il passthrough con un endpoint applicativo dentro Geohub sul modello di Maphub:
  resta una strada valida, ma richiede il rilascio di tutte le app e va affrontata a servizio
  ripristinato, in un ticket separato.
- Kibana: non viene incluso nello stack, per non tenere acceso un consumo costante di memoria per
  uno strumento di uso occasionale.
- Aggiornamenti di versione di Elasticsearch oltre a quanto serve per far ripartire il servizio.
- Indicizzazione dei POI: oggi non esiste e non viene introdotta qui.
- Qualsiasi modifica alla logica, ai comandi o allo schedule di indicizzazione di Geohub.

## Moduli toccati

Repo principale `geohub` (unico repo di codice toccato; nessun submodule presente):

- Configurazione dell'ambiente di produzione: valori delle chiavi `ELASTIC_*` lette da
  `config/services.php`. Nessun file del repo viene modificato per questo.
- `docker-compose.yml` — se si sceglie di far convivere i nuovi servizi nello stack di Geohub
  invece che in uno stack a sé.
- `docs/features/8857-ricostruzione-elastic-per-geohub-come-container/` — artefatti di questo
  lavoro.
- `docs/knowledge/` e `CLAUDE.md` — pagina di conoscenza su come Geohub parla con Elastic e come
  si ripristina il servizio.

Repo `elastic-passthrough` (fuori da questo repo): nessuna modifica al codice applicativo. Va
allineato `master` a `develop` (fast-forward, 10 commit, nessun conflitto) e messo un tag sul
commit portato in produzione. Il suo `docker-compose.yml` va adattato all'assetto deciso qui:
stack a sé, volume persistente, 9200 non pubblicata, ascolto sulla sola interfaccia locale, niente
Kibana.

Nessun file di `wm-core`, `wm-webapp` o delle app mobili viene toccato: è il vincolo che tiene in
piedi la retrocompatibilità.
