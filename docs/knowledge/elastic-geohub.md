# Ricerca delle tracce: come Geohub usa Elastic

## Come funziona oggi

Geohub **scrive soltanto** su Elasticsearch: nel repo non esiste una sola query di lettura.
Mantiene un indice per app, chiamato `<ELASTIC_PREFIX>_<app_id>` (il default del prefisso è
`geohub_app`), e ci tiene dentro le tracce con i dati che servono a cercarle e a filtrarle.

Chi legge sta fuori (quanto segue è stato accertato nel cantiere oc:8857 leggendo `wm-core` e
`wm-webapp`, repo esterni a questo): la webapp e le app mobili, attraverso `wm-core`, chiamano in GET anonima un
servizio intermedio — `elastic-passthrough`, un piccolo Express — che traduce la querystring
(`app`, `query`, `layer`, `filters`) in una query Elastic e restituisce `hits` più le
`aggregations` per temi, attività e layer. Le app non parlano mai con Elastic direttamente, e
l'URL che chiamano è scritto nel loro sorgente: una volta pubblicate, quell'indirizzo non si può
più cambiare.

Se l'indice non risponde, nelle app restano ferme la lista tracce della home, la ricerca
testuale, i filtri e l'elenco nel viewport della mappa. Non c'è ripiego su altre API: `wm-core`
registra il fallimento e lascia lo stato vuoto.

Due dettagli che dal codice non si deducono: `ELASTIC_KEY` ed `ELASTIC_HTTP` esistono nella
configurazione ma non le legge nessuno, e il nodo deve restare sulla serie 7.x perché è il client
PHP a vincolarlo. Tutto il resto — chiavi di connessione, mapping dell'indice, observer, comando
di ricostruzione, pianificazione notturna — si legge nel codice, e lì va cercato: ricopiarlo qui
vorrebbe dire avere due versioni che divergono al primo cambio.

Due cose fanno sbagliare chi non le conosce:

- **`geohub:index-tracks` senza `--info-elastic` non ricostruisce solo l'indice**: entra in
  `buildAllRoutine()` e riscrive anche `config.json` e il geojson dei POI dell'app,
  sovrascrivendoli senza conservare il file precedente. Per il solo Elastic il flag è
  obbligatorio.
- **Le job di indicizzazione non creano l'indice con il mapping definito**: indicizzano e basta.
  Se l'indice non esiste, Elasticsearch lo crea deducendo il mapping dai dati, quindi senza
  `name_keyword` di tipo `keyword` e senza `max_result_window: 50000`. La ricerca risponde, ma
  ordina e limita in modo diverso, senza alcun errore. Si rimedia rilanciando
  `geohub:index-tracks <app_id> --info-elastic`, che cancella e ricrea l'indice.

## Perché così

- **Un servizio davanti al nodo, invece delle app che interrogano Elastic** (oc:8857): il
  passthrough dà alle app un'interfaccia semplice e stabile senza esporre la sintassi di Elastic.
  L'effetto collaterale è che il nodo può restare privato, perché a raggiungerlo è un solo
  processo, lato server.
- **Il nodo Elastic non è raggiungibile da internet** (oc:8857): i soli client sono il
  passthrough e Geohub. Pubblicare la porta del nodo non serve a nessuno e allarga la superficie
  di attacco; vale la pena ricordare che le porte pubblicate da Docker non passano per le regole
  di firewall del sistema.
- **La serie di Elastic resta la 7.x** (oc:8857): è il client PHP a vincolarla. Un nodo 8.x fa
  fallire l'indicizzazione per via del controllo di prodotto che il client esegue.
- **La versione in produzione del passthrough si identifica con un tag** (oc:8857): durante il
  ripristino il codice realmente in esecuzione non era confrontabile con nessun branch, e questo
  ha fatto perdere tempo in ricostruzioni a naso.

## Come ci siamo arrivati

- **Un server dedicato per Elastic** (fino a oc:8857, superata): il nodo e il passthrough
  giravano in Docker su una macchina a parte. È stata eliminata per errore e il provider non ha
  permesso di ripristinarla; il volume dei dati è andato perso con lei. Si è scelto di non
  rifare una macchina dedicata: due container stanno accanto a Geohub senza costo aggiuntivo, e
  c'è un servizio in meno da mantenere.
- **Sostituire il passthrough con un endpoint dentro Geohub** (valutata in oc:8857, scartata):
  è la forma che Maphub usa già in produzione, e toglierebbe un servizio. Richiede però di
  cambiare l'URL nel sorgente dei client e di rilasciare tutte le app: quelle già installate non
  ne beneficerebbero, e resterebbero senza ricerca. Resta una strada valida per il futuro, da
  affrontare a servizio ripristinato.
- **Kibana nello stack** (valutata in oc:8857, scartata): consumo di memoria costante per uno
  strumento di uso occasionale, su una macchina che ospita anche l'applicazione.
