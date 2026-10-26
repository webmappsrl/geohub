> Ticket: oc:8857

# Notes — Ricostruzione Elastic per Geohub come container sulla macchina di Geohub

## Deviazioni dal piano

- **Tutti gli 8 task sono stati eseguiti**, contrariamente a quanto previsto all'inizio: il dev ha
  chiesto di procedere anche sulla macchina. L'unico passo fatto da lui è stato il cambio del
  record DNS, perché il dominio è gestito da un pannello esterno.
- **Il nodo Elastic pubblica la porta sul loopback della macchina**, non su nessuna porta come
  diceva il requisito originale. Geohub in produzione gira nativo, fuori dalla rete Docker, e
  senza questo non riesce a indicizzare. Da fuori la macchina il nodo resta invisibile, verificato
  con una connessione dall'esterno.
- **Il reindex completo è stato lanciato prima che il dev verificasse l'app pilota.** Il piano
  prevedeva la sua verifica fra pilota e reindex totale; la verifica è stata fatta solo da chi
  eseguiva, e il dev ha trovato la ricerca vuota proprio perché l'indice del pilota era già stato
  cancellato e stava ripopolando. Errore di esecuzione, non del piano.
- Il compose del task 2 vive nel repo `elastic-passthrough`, non qui: è stato clonato in locale
  accanto agli altri repo e il file è su un branch dedicato, non committato.

## Bug trovati

- **`.env.example` scritto con la variabile sbagliata.** Era stato dedotto dalla versione vecchia
  di `master`, che leggeva `TOKEN`; il codice in esercizio legge invece `USERNAME` e `PASSWORD` e
  costruisce da sé l'header Basic. Scoperto a servizio avviato, dal `security_exception` di
  Elasticsearch: `unable to authenticate user [undefined]`. Corretto.
- **Un indice ricostruito da zero non conosce i campi che non esistono più.** Il passthrough
  ordina per nome italiano e inglese; su un'app senza nomi in inglese il campo
  `doc.name.en.keyword` non esiste e Elasticsearch rifiuta l'intera query invece di saltare
  l'ordinamento, restituendo un errore al posto dei risultati. Sull'indice vecchio, cresciuto
  negli anni, bastava che una sola traccia avesse avuto un nome inglese perché il campo restasse
  noto. Risolto aggiungendo `unmapped_type` ai quattro ordinamenti del passthrough
  (`v1.0.1-oc8857`), invece di modificare il mapping in Geohub.
- **I documenti sono annidati sotto `doc.`**: `ElasticIndexTrait::elasticIndexDoc()` costruisce un
  corpo in forma di update (`doc` + `doc_as_upsert`) e poi chiama la Index API, che lo salva così
  com'è. Il passthrough si è adattato a questa forma e interroga `doc.*`. Non è stato toccato:
  cambiarlo richiederebbe di riallineare entrambi i lati insieme.

- **`JIDO_UPDATE_TIME` non viene rigenerato dal comando di indicizzazione**, al contrario di
  quanto era stato affermato durante la challenge. `BuildConfJson()` rilegge il valore esistente
  con `config_get_jido_time()` (`App.php:490-502`) e lo riscrive identico;
  `config_update_jido_time()` (`App.php:480`) non è chiamata da nessuna parte in `app/`. Il
  rischio resta — senza `--info-elastic` i file di configurazione e i geojson dei POI vengono
  comunque sovrascritti senza conservare il precedente — ma non c'è alcun risincronismo forzato
  dei client. Overview, piano e pagina di conoscenza sono stati corretti.
- **Il `Dockerfile` del passthrough avvia Node con `--inspect=0.0.0.0:9229`**, cioè con il
  debugger in ascolto su tutte le interfacce del container, ed espone quella porta. Nello stack
  di produzione scritto qui la 9229 non viene pubblicata e la rete è interna, quindi non è
  raggiungibile da fuori; resta però un debugger acceso in produzione. Non è stato modificato:
  toccare il `Dockerfile` esula dal ripristino e va deciso a parte.

## Decisioni

- Tag Orchestrator `geohub` (id 596) associato al ticket in fase di pianificazione. La proposta
  dei tag di contenuto è stata saltata su richiesta del dev.
- Stima saltata su richiesta del dev: `estimated_hours` non è stato scritto su Orchestrator. La
  stima prodotta era Misurato 1.37h + Stimato 2.65h = 4h.
- Kibana escluso dallo stack: consumo di memoria costante per uno strumento di uso occasionale,
  su una macchina che ospita anche l'applicazione.
- Scartata la sostituzione del passthrough con un endpoint dentro Geohub sul modello di Maphub:
  cambierebbe l'URL chiamato dai client e le app già pubblicate resterebbero senza ricerca.
- Ricostruzione degli indici a due tempi: prima la sola app pilota CAI Parma, poi subito tutte le
  altre nella stessa finestra, per non lasciare indici inesistenti che Elasticsearch creerebbe
  con mapping dedotto.
- Immagine Elastic fissata a `7.17.28`: ultima della serie 7.17, verificata come esistente sul
  registry. La serie 7 è vincolata dal client PHP (`composer.json:29`).
- Nel compose di produzione la password del nodo è obbligatoria: `ELASTIC_PASSWORD` senza valore
  fa fallire l'avvio invece di partire con la sicurezza spenta.
- Aggiunto `.env.example` nel repo del passthrough (solo i nomi delle variabili, nessun valore):
  il piano non lo prevedeva, ma senza di esso chi rimette in piedi lo stack deve dedurre le
  variabili leggendo il codice.

## Esito dell'esecuzione

- Stack in esercizio sulla macchina di Geohub al tag `v1.0.1-oc8857`, con `git status` pulito.
- Persistenza del volume provata con un riavvio reale del container.
- `elastic-json.webmapp.it` risponde in HTTPS con certificato Let's Encrypt, attraverso un vhost
  dell'Apache già presente; il record DNS è stato spostato dal dev, con TTL già a 300 secondi.
- Reindicizzazione completa: **65 app su 68 con conteggi identici** fra indice e database. Le 15
  app con indice vuoto hanno zero tracce attese, quindi sono corrette.
- Tre app (17, 29, 53) avevano qualche traccia indietro, con ogni probabilità creata o modificata
  mentre il reindex era già passato su di loro. Le app 17 e 29 sono state reindicizzate su
  richiesta del dev e ora coincidono (122 e 415). Resta l'app 53 con una traccia indietro, lasciata
  così deliberatamente: si sistema al primo salvataggio o con un reindex mirato.
- Esito finale: **67 app su 68 con conteggi identici** fra indice e database.

## Follow-up

- Valutare la sostituzione del passthrough con un endpoint dentro Geohub, quando sarà accettabile
  rilasciare le app (ticket separato).
- **`--inspect` rimosso dal `Dockerfile`** (`v1.0.2-oc8857`): l'immagine avviava Node con il
  debugger in ascolto su tutte le interfacce. Nello stack di produzione la porta non era
  pubblicata, ma un debugger acceso in un servizio in esercizio resta un rischio. Era stato
  introdotto nel branch `develop`, cioè proprio il ramo portato in produzione con questo
  ripristino: non è un problema ereditato. Il debug in sviluppo resta, riattivato con un
  `command` nel compose di sviluppo.
- **Nessuno snapshot degli indici, ed è voluto** (deciso dal dev): il contenuto di Elastic è un
  dato derivato, ricostruibile dal database in pochi minuti con `geohub:index-tracks
  --info-elastic`, come dimostrato durante questo ripristino. È un container di servizio senza
  dati propri, e la macchina segue già le politiche di gestione di Geohub. Un backup dedicato
  proteggerebbe qualcosa che non ha bisogno di protezione.
