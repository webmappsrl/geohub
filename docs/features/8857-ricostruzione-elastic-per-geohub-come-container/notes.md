> Ticket: oc:8857

# Notes — Ricostruzione Elastic per Geohub come container sulla macchina di Geohub

## Deviazioni dal piano

- **Dei 8 task del piano, in questa sessione sono stati eseguiti solo il 2 e l'8.** Gli altri sono
  operazioni sulla macchina, su Apache e sul DNS, che vanno fatte dal dev: restano nel piano come
  istruzioni con i comandi e l'esito atteso.
- Il compose del task 2 vive nel repo `elastic-passthrough`, non qui: è stato clonato in locale
  accanto agli altri repo e il file è su un branch dedicato, non committato.

## Bug trovati

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

## Follow-up

- Valutare la sostituzione del passthrough con un endpoint dentro Geohub, quando sarà accettabile
  rilasciare le app (ticket separato).
- Valutare la rimozione di `--inspect` dal `Dockerfile` del passthrough.
- Il nodo nuovo non ha né snapshot né monitoraggio: il ticket nasce da una perdita di dati e non
  introduce nulla che impedisca di ripeterla. Vale un ticket a sé.
