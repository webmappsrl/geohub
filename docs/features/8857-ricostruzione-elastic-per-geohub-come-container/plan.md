> Ticket: oc:8857

# Ricostruzione Elastic per Geohub — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rimettere in esercizio Elasticsearch e il servizio `elastic-passthrough` come due container sulla macchina di Geohub, senza che app mobili e webapp già pubblicate si accorgano di nulla.

**Architecture:** Stack Docker a sé (Geohub in produzione non è dockerizzato), con Elastic 7.x raggiungibile solo dalla rete Docker interna e il passthrough in ascolto sulla sola interfaccia locale. L'unico ingresso pubblico è un vhost dell'Apache già in esercizio, che termina il TLS per `elastic-json.webmapp.it`. Gli indici, persi con il volume, si ricostruiscono dal database con il comando già esistente di Geohub, partendo da una sola app pilota.

**Tech Stack:** Docker Compose, Elasticsearch 7.x, Node 16 (Express), Apache come reverse proxy, Laravel 8 / PHP 7.4 lato Geohub.

**Spec:** [overview.md](overview.md)

## Global Constraints

- Il nodo Elastic resta sulla serie **7.x**, con tag di versione esplicito: `composer.json:29` vincola il client a `elasticsearch/elasticsearch: ^7.15` e un nodo 8.x fa fallire l'indicizzazione. Mai `latest`.
- La porta **9200 non compare mai** fra le porte pubblicate.
- Il passthrough ascolta **solo sull'interfaccia locale** della macchina.
- Il comando di indicizzazione si lancia **sempre** con `--info-elastic`.
- **Nessun segreto nei repo**: le credenziali passano da variabili d'ambiente e da file non versionati. Entrambi i repo sono pubblici: nessun IP, hostname interno o dettaglio di configurazione dei server nei file e nei commit.
- **Nessuna modifica** a schedule, comandi e logica di indicizzazione di Geohub.
- Documentazione in italiano. Commit con scope `oc:8857`.
- **Nessuna operazione git viene eseguita**: i commit sono istruzioni per il dev.

## Review Focus

1. **Indice inesistente al primo salvataggio da Nova** — `TrackElasticIndexTrait.php:42-45` indicizza senza creare: Elasticsearch genera un mapping dinamico, senza `name_keyword` di tipo `keyword` né `max_result_window: 50000`, e la ricerca risponde con ordinamenti e limiti sbagliati senza alcun errore. Coperto dal Task 7, che confronta il mapping reale con quello atteso.
2. **Prefisso disallineato** — se `ELASTIC_PREFIX` non combacia con il nome indice atteso dal passthrough, tutto risponde 200 con zero risultati. Coperto dal Task 6.
3. **Volume dichiarato ma non persistente** — permessi sbagliati sulla directory dati fanno ripartire Elastic da vuoto senza errori evidenti. Coperto dal Task 3, con riavvio reale.
4. **Errore di connessione mascherato** — `getClient()` non ha `return` nel ramo di errore (`ElasticIndexTrait.php:28-30`): un guasto di rete appare come errore fatale su `null` dentro le job, non come problema di connettività. Coperto dal Task 5.
5. **Route legacy `/search` e `/track`** — le app più vecchie potrebbero usarle: sono il motivo per cui si è scelta questa strada e vanno verificate esplicitamente. Coperte dal Task 6.

---

### Task 1: Allineare il repo del passthrough e fissare la versione

Repo: `webmappsrl/elastic-passthrough` (non `geohub`).

**Files:**
- Modify: branch `master` del repo `elastic-passthrough`

**Interfaces:**
- Produces: un tag immutabile che identifica la versione portata in produzione, usato dal Task 2 per il build dell'immagine.

- [ ] **Step 1: Verificare che l'allineamento sia un fast-forward**

Run: `gh api repos/webmappsrl/elastic-passthrough/compare/master...develop --jq '{ahead: .ahead_by, behind: .behind_by}'`
Expected: `ahead` maggiore di zero, `behind` uguale a `0`. Se `behind` non è `0`, fermarsi: l'allineamento non è più banale e va deciso col dev.

- [ ] **Step 2: Allineare `master` a `develop`**

Istruzione per il dev: aprire una PR da `develop` a `master` sul repo `elastic-passthrough` e fonderla senza squash, così la storia resta confrontabile.

- [ ] **Step 3: Taggare il commit portato in produzione**

Istruzione per il dev, sul repo `elastic-passthrough`:

```bash
git tag -a v1.0.0-oc8857 -m "Versione ripristinata sulla macchina di Geohub (oc:8857)"
git push origin v1.0.0-oc8857
```

- [ ] **Step 4: Verificare che il tag esista**

Run: `gh api repos/webmappsrl/elastic-passthrough/tags --jq '.[].name'`
Expected: l'elenco contiene `v1.0.0-oc8857`.

---

### Task 2: Scrivere il compose di produzione dello stack

Repo: `elastic-passthrough`.

**Files:**
- Create: `compose.prod.yml` nel repo `elastic-passthrough`
- Reference: `docker-compose.yml` dello stesso repo (quello del vecchio server) e `docker-compose.yml:52-68` di `geohub`, **da non copiare**

**Interfaces:**
- Consumes: il tag del Task 1.
- Produces: i nomi di servizio `es01` (Elastic) e `elastic-passtrough`, usati dai task successivi; il passthrough raggiunge Elastic all'indirizzo `http://es01:9200`, come già fa il codice su `develop` (`index.js:8`).

- [ ] **Step 1: Scrivere `compose.prod.yml` con due soli servizi**

Il file definisce `es01` e `elastic-passtrough` su una rete bridge dedicata, e nient'altro: niente Kibana.

Per `es01`: immagine `docker.elastic.co/elasticsearch/elasticsearch:7.17.28` (serie 7, versione esplicita), `discovery.type=single-node`, `bootstrap.memory_lock=true`, `ES_JAVA_OPTS=-Xms512m -Xmx512m`, `ulimits.memlock` a `-1`, `restart: always`, un volume Docker con nome montato su `/usr/share/elasticsearch/data`, **nessuna chiave `ports`**.

Per `elastic-passtrough`: `build: .`, `restart: always`, `depends_on: [es01]`, `env_file` che punta a un file non versionato per il token, e una sola porta pubblicata, legata all'interfaccia locale, nella forma `127.0.0.1:3000:3000`.

- [ ] **Step 2: Verificare che il file non contenga quello che non deve**

Run: `grep -nE '^\s*-\s*"?9200|kibana|latest' compose.prod.yml`
Expected: nessun risultato.

- [ ] **Step 3: Verificare che il volume sia dichiarato**

Run: `grep -nA2 '^volumes:' compose.prod.yml`
Expected: un volume con nome, referenziato dal servizio `es01`.

- [ ] **Step 4: Aggiungere il file del token a `.gitignore`**

Il file d'ambiente con il token di accesso a Elastic non entra nel repo. Verifica: `git check-ignore -v <nome-file>` deve riportare la regola che lo esclude.

- [ ] **Step 5: Commit** (istruzione per il dev, sul repo `elastic-passthrough`)

```bash
git add compose.prod.yml .gitignore
git commit -m "feat(oc:8857): add production compose with private elastic node"
```

---

### Task 3: Avviare lo stack e provare la persistenza

Eseguito sulla macchina di Geohub.

**Files:**
- Nessun file del repo: operazioni sulla macchina.

**Interfaces:**
- Consumes: `compose.prod.yml` del Task 2.
- Produces: un nodo Elastic attivo e raggiungibile solo dalla rete Docker, su cui i task successivi scrivono.

- [ ] **Step 1: Verificare che Docker sia installato e attivo sulla macchina**

Run: `docker version --format '{{.Server.Version}}'`
Expected: una versione. In caso di errore fermarsi e segnalarlo al dev: Geohub non è dockerizzato e la presenza di Docker su questa macchina non è scontata.

- [ ] **Step 2: Avviare lo stack**

Run: `docker compose -f compose.prod.yml up -d`
Expected: entrambi i container in stato `running`.

- [ ] **Step 3: Verificare che il nodo risponda dalla rete interna e non dall'esterno**

Run: `docker compose -f compose.prod.yml exec elastic-passtrough wget -qO- http://es01:9200/_cluster/health`
Expected: JSON con `"status"` valorizzato.

Run: `curl -s -m 5 -o /dev/null -w '%{http_code}' http://127.0.0.1:9200`
Expected: `000`, cioè connessione rifiutata — la porta non è pubblicata.

- [ ] **Step 4: Provare la persistenza con un riavvio reale**

Creare un documento di prova, riavviare il container e rileggerlo:

```bash
docker compose -f compose.prod.yml exec elastic-passtrough \
  wget -qO- --post-data '{"prova":"oc8857"}' --header 'Content-Type: application/json' \
  http://es01:9200/prova_persistenza/_doc/1
docker compose -f compose.prod.yml restart es01
# attendere che il nodo torni su, poi:
docker compose -f compose.prod.yml exec elastic-passtrough \
  wget -qO- http://es01:9200/prova_persistenza/_doc/1
```

Expected: la rilettura restituisce `"found":true`. Se restituisce `found:false` o 404, il volume non sta funzionando: fermarsi qui, è esattamente il guasto che ha causato questo ticket.

- [ ] **Step 5: Rimuovere l'indice di prova**

Run: `docker compose -f compose.prod.yml exec elastic-passtrough wget -qO- --method=DELETE http://es01:9200/prova_persistenza`
Expected: `"acknowledged":true`.

---

### Task 4: Pubblicare il servizio su Apache e spostare il DNS

Eseguito sulla macchina di Geohub e sul pannello DNS.

**Files:**
- Nessun file dei repo: configurazione del server.

**Interfaces:**
- Consumes: il passthrough in ascolto su `127.0.0.1:3000` dal Task 3.
- Produces: `https://elastic-json.webmapp.it` funzionante, che i Task 6 e 7 interrogano.

- [ ] **Step 1: Abbassare il TTL del record DNS prima di toccarlo**

Istruzione per il dev: portare il TTL di `elastic-json.webmapp.it` a un valore basso e attendere che il vecchio TTL sia scaduto, **prima** di cambiare la destinazione. È il passo che rende correggibile in minuti un errore di destinazione; una volta spostato il record non esiste una destinazione precedente a cui tornare.

- [ ] **Step 2: Aggiungere il vhost ad Apache**

Un vhost per `elastic-json.webmapp.it` che gira le richieste su `http://127.0.0.1:3000/`, con il certificato emesso dallo strumento già in uso sulla macchina. Non va introdotto un secondo terminatore TLS: Apache è già in esercizio e due processi non possono condividere le porte 80 e 443.

- [ ] **Step 3: Verificare la configurazione prima di ricaricare**

Run: `apachectl configtest`
Expected: `Syntax OK`.

- [ ] **Step 4: Spostare il record DNS e verificare la risposta**

Run: `curl -s -o /dev/null -w '%{http_code}\n' https://elastic-json.webmapp.it/`
Expected: `200` con il corpo `Hello World!` servito dalla route di base del passthrough (`index.js`). Un errore di certificato qui va risolto prima di proseguire: i client nativi delle app trattano l'errore TLS come fallimento definitivo, non come un 5xx da ritentare.

- [ ] **Step 5: Riportare il TTL al valore abituale**

Solo dopo che il servizio risponde correttamente.

---

### Task 5: Puntare Geohub al nodo nuovo

Repo: `geohub` — **solo ambiente di produzione, nessun file del repo viene modificato.**

**Files:**
- Modify: variabili d'ambiente di produzione di Geohub (non versionate)
- Reference: `config/services.php:33-40`

**Interfaces:**
- Consumes: il nodo del Task 3.
- Produces: un Geohub capace di scrivere sugli indici, usato dai Task 6 e 7.

- [ ] **Step 1: Creare su Elastic l'utente usato da Geohub**

Geohub si autentica con basic auth (`ElasticIndexTrait.php:15-19`). L'utente e la password vanno creati sul nodo nuovo e non devono comparire in nessun repo.

- [ ] **Step 2: Aggiornare le chiavi di connessione**

Aggiornare `ELASTIC_HOST`, `ELASTIC_USERNAME`, `ELASTIC_PASSWORD` e `ELASTIC_PREFIX` nell'ambiente di produzione. `ELASTIC_PREFIX` deve valere esattamente `geohub_app`, che è il valore con cui il passthrough costruisce il nome dell'indice (`index.js:18`) e il default usato da Geohub (`App.php:463`).

- [ ] **Step 3: Ripulire le chiavi non più usate**

Svuotare `ELASTIC_KEY` ed `ELASTIC_HTTP`: sono presenti in `config/services.php:35-36` ma non le legge nessuno, e lasciarle con i dati della macchina dismessa è una trappola per il prossimo intervento.

- [ ] **Step 4: Ricaricare la configurazione e verificare la connessione**

Run: `php artisan config:cache` e poi `php artisan tinker --execute="dd(app(\App\Models\App::class)->getClient() !== null);"`
Expected: `true`. Se stampa `false`, la connessione non è riuscita: `getClient()` non ritorna nulla nel ramo di errore (`ElasticIndexTrait.php:28-30`), quindi proseguire produrrebbe errori fatali su `null` dentro le job, con messaggi che non parlano di Elastic. Il motivo vero sta nei log, alla riga `Connection to Elasticsearch failed`.

- [ ] **Step 5: Verificare che il queue worker sia attivo**

Senza worker il comando di indicizzazione accoda e non indicizza nulla. Expected: il processo delle code risulta in esecuzione.

---

### Task 6: Ricostruire l'indice dell'app pilota e verificarlo

Repo: `geohub` — esecuzione di comandi, nessuna modifica di codice.

**Files:**
- Reference: `app/Console/Commands/EcTrackIndexCommand.php:16-19`, `app/Models/App.php:459-467`

**Interfaces:**
- Consumes: la configurazione del Task 5 e il servizio pubblicato del Task 4.
- Produces: la prova che mapping, prefisso e catena di lettura funzionano, prima di impegnare ore di coda nel Task 7.

- [ ] **Step 1: Ricavare l'id dell'app pilota**

Run: `php artisan tinker --execute="dd(App\Models\App::where('name','ilike','%parma%')->get(['id','name'])->toArray());"`
Expected: una riga con l'id di CAI Parma. Annotarlo: serve nei passi seguenti come `<APP_ID>`.

- [ ] **Step 2: Ricostruire il solo indice dell'app pilota**

Run: `php artisan geohub:index-tracks <APP_ID> --info-elastic`
Expected: il comando termina senza errori e accoda una job per ogni traccia.

Il flag `--info-elastic` è obbligatorio: senza di esso il comando entra in `buildAllRoutine()` (`EcTrackIndexCommand.php:72`), che oltre a Elastic riscrive `config.json` e il geojson dei POI dell'app, sovrascrivendoli senza conservare il file precedente.

- [ ] **Step 3: Attendere che la coda si svuoti e controllare i fallimenti**

Run: `php artisan queue:failed`
Expected: nessuna job fallita riconducibile all'indicizzazione.

- [ ] **Step 4: Verificare che il mapping sia quello definito, non uno dedotto**

Leggere il mapping dell'indice `geohub_app_<APP_ID>` e confrontarlo con quello di `createElasticIndex()`.
Expected: `name_keyword` è di tipo `keyword` e `max_result_window` vale `50000`. Se il mapping è diverso, l'indice è stato creato al volo da un salvataggio da Nova: va cancellato e ricostruito con lo stesso comando.

- [ ] **Step 5: Verificare la catena completa dall'esterno, incluse le route legacy**

```bash
curl -s "https://elastic-json.webmapp.it/v2/search?app=geohub_app_<APP_ID>" | head -c 400
curl -s "https://elastic-json.webmapp.it/search?id=<APP_ID>" | head -c 200
curl -s "https://elastic-json.webmapp.it/track?app=geohub_app_<APP_ID>&id=<TRACK_ID>" | head -c 200
```

Expected: la prima restituisce `hits` non vuoto e le `aggregations` con `themes`, `activities` e `layers`; le altre due rispondono senza errore. Una risposta `200` con zero risultati significa quasi sempre prefisso disallineato: l'indice che Geohub ha scritto non è quello che il passthrough interroga.

- [ ] **Step 6: Verificare sulla webapp**

Aprire la webapp dell'app pilota e controllare che lista tracce della home, ricerca testuale e filtri tornino a funzionare.

---

### Task 7: Ricostruire gli indici di tutte le app

Repo: `geohub` — esecuzione di comandi.

**Files:**
- Reference: `app/Console/Commands/EcTrackIndexCommand.php:80-85`

**Interfaces:**
- Consumes: la verifica riuscita del Task 6.

- [ ] **Step 1: Lanciare la ricostruzione completa, subito dopo la verifica del pilota**

Run: `php artisan geohub:index-tracks --info-elastic`
Expected: il comando cicla su tutte le app e accoda le job.

Questo passo non va rimandato a giorni dopo: finché un indice non esiste, un qualsiasi salvataggio di traccia da Nova lo fa creare a Elasticsearch con mapping dedotto (`TrackElasticIndexTrait.php:42-45`), e la ricerca di quell'app risponde con ordinamenti e limiti sbagliati senza segnalare nulla.

- [ ] **Step 2: Attendere lo svuotamento della coda**

Expected: coda vuota. Il tempo dipende dal numero di app e di tracce e non è comprimibile.

- [ ] **Step 3: Controllare le job fallite**

Run: `php artisan queue:failed`
Expected: nessuna fallita. Se ce ne sono, leggerne il messaggio: un errore su `null` indica un problema di connessione a Elastic, non un difetto dei dati.

- [ ] **Step 4: Confronto numerico fra documenti indicizzati e tracce attese**

Per ogni indice `geohub_app_<id>`, confrontare il `_count` dell'indice con il numero di tracce che l'app espone attraverso i suoi layer.
Expected: i due numeri coincidono per ogni app. È questo il criterio di fine lavoro: «la webapp mostra qualcosa» non distingue un indice completo da uno a metà, e le app a basso traffico nessuno le aprirà per mesi.

- [ ] **Step 5: Verificare un'app a basso traffico**

Scegliere un'app diversa dal pilota e ripetere la chiamata `/v2/search` del Task 6.
Expected: `hits` non vuoto.

---

### Task 8: Registrare la conoscenza nel repo

Repo: `geohub`.

**Files:**
- Create: `docs/knowledge/elastic-geohub.md`
- Modify: `CLAUDE.md` (tabella della sezione `## Conoscenza`)

- [ ] **Step 1: Scrivere la pagina di conoscenza**

La pagina risponde a due domande: come Geohub parla con Elastic (chiavi di configurazione, nome degli indici, chi scrive e chi legge, il ruolo del passthrough e il fatto che il backend non legge mai) e come si ripristina il servizio se sparisce di nuovo. Struttura: `## Come funziona oggi`, `## Perché così`, `## Come ci siamo arrivati`, con il ticket accanto a ogni scelta.

Vincolo: nessun IP, nessun percorso sul server, nessuna credenziale. Il repo è pubblico.

- [ ] **Step 2: Aggiungere la riga nell'indice del `CLAUDE.md`**

Una riga sola nella tabella di `## Conoscenza`, con il link alla pagina.

- [ ] **Step 3: Verificare che il link funzioni**

Run: `test -f docs/knowledge/elastic-geohub.md && grep -c 'elastic-geohub.md' CLAUDE.md`
Expected: il file esiste e il `CLAUDE.md` lo cita una volta.

- [ ] **Step 4: Commit** (istruzione per il dev)

```bash
git add docs/knowledge/elastic-geohub.md CLAUDE.md docs/features/8857-ricostruzione-elastic-per-geohub-come-container/
git commit -m "docs(oc:8857): add elastic knowledge page and feature artifacts"
```
