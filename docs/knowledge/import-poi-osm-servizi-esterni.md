# Import dei POI da OSM: i servizi esterni e le loro regole

## Come funziona oggi

L'import dei POI da OpenStreetMap (provider `osmpoi`, oggi usato da CAI Parma) dipende da tre
servizi pubblici che non sono nostri:

- **Overpass** (`overpass-api.de`, gestito da FOSSGIS): fornisce ogni sera la lista dei nodi da
  importare per ciascun endpoint, con la data dell'ultima modifica
  (`OutSourceImporterListOSMPoi`, `curlRequestOverpass`). Le query stanno nel codice; l'istanza
  si cambia con `OVERPASS_INTERPRETER_URL`.
- **API di OSM** (`api.openstreetmap.org`): i dati del singolo nodo (`OsmServiceProvider`).
- **Wikimedia Commons**: per i POI con il tag `wikimedia_commons`, i metadati della foto (API)
  e il file.

Tutte le chiamate fatte con `CurlServiceProvider` mandano lo User-Agent di
`config('geohub.http_user_agent')`; il download delle foto usa `geohub.wikimedia_user_agent`.
Senza User-Agent Overpass risponde 406 e Wikimedia 403: non è un malfunzionamento passeggero, e
riprovare non serve.

Il retry verso Overpass segue le regole degli operatori: almeno 40 s fra un tentativo e
l'altro, 3 tentativi, nessun nuovo tentativo su 400/403/406 o connessione rifiutata, e una pausa
anche prima di restituire l'errore, perché lo script notturno lancia più query di seguito. Chi
insiste dopo un 406 o un 429 viene bloccato per IP: il blocco è automatico e scade solo se il
client smette di sbagliare.

Due cose fanno sbagliare chi non le conosce:

- **Un import fermo non si vede in mappa**: i POI già importati restano dove sono, mancano solo
  i nuovi e le modifiche. Il segnale sta nel log dell'importer, che per mesi può riportare solo
  errori di rete.
- **Per reimportare un singolo POI** si usa `geohub:out_source_importer` con
  `--single_feature`, che legge il nodo dall'API di OSM e non cancella nulla. Il sync dei media
  va fatto prima di quello dei POI, altrimenti il POI resta senza foto.

## Perché così

- **User-Agent su ogni chiamata** (oc:9021): dal 19/04/2026 gli operatori di Overpass rifiutano
  gli script senza un User-Agent che li identifichi, e Wikimedia fa lo stesso per l'API.
- **Retry lento e selettivo** (oc:9021): gli operatori chiedono almeno 30 s dopo un errore e
  bloccano chi riceve ripetutamente 429 o 406. Il vecchio retry aveva fatto bloccare l'IP del
  server di produzione.
- **Istanza Overpass configurabile** (oc:9021): le regole FOSSGIS sconsigliano di scrivere
  l'indirizzo nel codice, e il server pubblico non garantisce disponibilità.
- **User-Agent dedicato per il download delle foto** (oc:8361): Wikimedia rifiutava i download
  fatti con `file_get_contents()`.

Il server pubblico di Overpass resta una dipendenza debole: secondo i suoi operatori gli usi
commerciali dovrebbero usare un'istanza propria o un fornitore a pagamento, oppure lavorare su
un estratto OSM filtrato.

## Come ci siamo arrivati

- **Chiamate senza User-Agent e retry a 1-8 s per 5 tentativi** (oc:9021, superato): hanno
  funzionato finché Overpass accettava client anonimi. Dal 28/04/2026 ogni corsa riceveva 406 e
  da agosto l'IP del server di produzione è stato bloccato.
