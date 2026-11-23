> Ticket: oc:9021

# Notes — Import POI OSM fermo: Overpass e Wikimedia rifiutano le richieste

Ticket di help desk, lavorato senza overview e piano: qui restano la diagnosi, le decisioni e
ciò che resta da fare.

## Diagnosi

Il cliente (CAI Parma) segnalava un luogo di posa nuovo, X0125, presente su OSM ma non in mappa.
Il dato su OSM era corretto. In realtà l'import notturno dei POI da OSM
(`scripts/cai_parma_osm_poi_updated_at.sh`, endpoint `osmpoi:caiparma_*`) era fermo dal
28/04/2026 per tutti gli endpoint: rifugi, bivacchi, punti acqua e luoghi di posa. Il log
`cai_parma_osm_poi_importer.log` mostra tre cause in sequenza:

- **dal 28/04/2026, HTTP 406 da overpass-api.de**: dal 19/04 gli operatori rifiutano gli
  script senza User-Agent, e `CurlServiceProvider` non lo impostava;
- **da agosto 2026, connessione rifiutata**: il blocco automatico dell'IP del server di
  produzione, scattato perché il retry insisteva a pochi secondi di distanza dopo ogni 406;
- **HTTP 403 dall'API di Wikimedia Commons** per i POI con il tag `wikimedia_commons`: anche
  questa chiamata passa da `CurlServiceProvider` senza User-Agent. È emerso tentando l'import
  manuale di X0125, che si fermava prima di creare la OSF.

## Decisioni

- **Call di team del 23/11/2026**: User-Agent sulla chiamata, prova in locale, deploy, una
  chiamata di prova dal server di produzione con il nuovo User-Agent, poi mail agli operatori di
  Overpass per chiedere lo sblocco, e ticket in waiting in attesa della risposta.
- **Retry allineato alle regole degli operatori** (decisione del dev dopo la call, che aveva
  lasciato il retry invariato): attesa minima 40 s (gli operatori ne chiedono almeno 30 dopo un
  errore), 3 tentativi invece di 5, nessun nuovo tentativo su 400/403/406 e su connessione
  rifiutata, 40 s di pausa anche prima di restituire l'errore finale, perché lo script esegue più
  query Overpass di seguito.
- **URL di Overpass configurabile** (`OVERPASS_INTERPRETER_URL`): le regole FOSSGIS sconsigliano
  di scrivere l'indirizzo del servizio nel codice, e serve per passare a un'altra istanza senza
  deploy.

## Bug trovati durante il lavoro

- `geohub:out_source_importer_updated_at` con `--single_feature` metteva nella lista solo la
  feature richiesta e il diff con le OSF esistenti cancellava tutti gli altri POI
  dell'endpoint. Ora con `--single_feature` la cancellazione si salta.
- Gli URL che Wikimedia Commons restituisce oggi contengono parametri `utm_*`: il nome del file
  salvato diventava `<sha1>.jpg?utm_source=commons`. Ora si ricava dal solo percorso dell'URL.
- Il log del retry contava un tentativo in meno nel messaggio di successo.

## Follow-up

- Dopo il deploy: una chiamata di prova dal server di produzione, poi la mail agli operatori di
  overpass-api.de (contatto delle regole d'uso FOSSGIS). Gli operatori, sul forum OSM, dicono che
  i blocchi per IP sono automatici e scadono se il client smette di sbagliare, mentre quelli per
  User-Agent vanno chiesti a mano.
- Quando l'import riparte, la corsa notturna recupera da sola i luoghi di posa mancanti (1995 su
  OSM, 1943 importati ad aprile) e le modifiche arretrate degli altri endpoint.
- Per inserire X0125 prima dello sblocco: `geohub:out_source_importer` (senza `_updated_at`) con
  `--single_feature=node/14395346629`, poi i passi di sync dello script notturno nell'ordine media
  → poi → `sync-ec-tags-from-osf --tag=osmid`. Il sync del POI aggancia la foto solo se l'EcMedia
  esiste già.
- Medio periodo: togliere la dipendenza da Overpass. Gli operatori chiedono di non usare il
  server pubblico per usi commerciali e suggeriscono estratti OSM filtrati (osmium) o istanze
  proprie; in call è emersa anche l'ipotesi del server di tiles vettoriali interno.
