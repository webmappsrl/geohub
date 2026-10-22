> Ticket: oc:8837

# Notes — Messa in sicurezza PHP2Shell sul server geohub

## Divergenze dal piano, task per task

### Task 2: raccolta evidenze sostituita dallo snapshot

Il dev ha stabilito che lo snapshot Hetzner (Task 1, fatto dal pannello) copre le evidenze: il
disco intero — log con tutte le rotazioni, DB, cron, file — è congelato fuori dal futuro della
macchina. La raccolta manuale (tar + dump + scp) era la variante della strategia del tag per
quando lo snapshot non è possibile, quindi non si esegue. Non viene preservato lo stato volatile
(processi in esecuzione): valutazione rimandata alla seconda attività (verifica di
compromissione), gestita dal dev.

### Task 3: abilitazione ESM bloccata da un repo apt dismesso

`ua enable esm-infra` falliva perché `apt update` si fermava su un repo di terze parti morto a
monte (pgdg per focal, nessun Release file). Il repo era già inservibile; la sua lista è stata
disabilitata per rinomina (`.disabled`, reversibile) e l'abilitazione è andata a buon fine.
L'attach ha anche installato snapd + canonical-livepatch (comportamento di default di `ua attach`).

### Task 5: LimitRequestBody corretto da 16M a 64M dopo la verifica via web

Il primo valore (16M) era stato calcolato sul `post_max_size` letto da CLI (8M). La verifica via
SAPI web ha mostrato il valore reale: 50M — la trappola CLI≠SAPI documentata in oc:8833 ha
colpito anche qui. Valore corretto a 64M (sopra il tetto PHP reale, margine per l'overhead):
nessun upload oggi possibile cambia comportamento. Verificato funzionalmente: POST 30M passa il
gate Apache, POST 80M → 413.

## Bug trovati

Nessun bug applicativo. Le scoperte d'infrastruttura emerse durante il lavoro **non sono
dettagliate qui perché il repo è pubblico**: sono registrate nel censimento di oc:8835 e nelle
note dev di oc:8837 (esposizioni chiuse con le disattivazioni, più quelle da valutare nella
seconda attività).

## Decisioni

- Stima `wm-estimate` (Misurato 1,45h + Stimato 2,4h = 3,85h) presentata e **saltata** dal dev:
  nessuna scrittura di `estimated_hours` su Orchestrator (22/10/2026).
- `old.geohub` escluso dal perimetro su decisione del dev (è una macchina separata, verificato).
- Geomixer2: disattivazione **permanente** decisa dal dev (vhost disabilitato, stop +
  `--restart=no`; container e dati preservati come evidenza, nulla cancellato).
- dev.geohub: spegnimento deciso dal dev (tutti i container `geohubdev` fermi con `--restart=no`),
  riaccensione a sua discrezione.
- Limiti di upload di prod **invariati** su decisione del dev; aggiunto solo `LimitRequestBody`
  sopra il tetto PHP reale.
- `expose_php off` aggiunto su conferma del dev (l'header `x-powered-by` non esce più).
- Verifica di compromissione approfondita e rotazione segreti: **seconda attività** gestita dal
  dev, fuori da questo ticket.
- Verifica funzionale dell'upload autenticato (Nova) rimandata dal dev («se poi ci sono problemi
  si risolvono»): unico criterio funzionale non coperto al momento della chiusura.
- Il token Ubuntu Pro è transitato in chiaro nella conversazione; il dev ha scelto di **non
  rigenerarlo** per ora.
- Fasi tag-ambiente e tag-contenuto saltate su indicazione del dev («qui è tutto pre Claude»).
- Riesame fissato ad **agosto 2027** se maphub non ha ancora sostituito geohub.

## Follow-up

- Seconda attività (dev): verifica di compromissione sulle evidenze dello snapshot + rotazione
  dei segreti del `.env` di prod.
- Verifica upload autenticato via Nova appena possibile (limiti invariati, atteso nessun impatto).
- oc:8835: valutare firewall host e esposizione Postgres insieme all'aggiornamento OS (riga già
  aggiunta al censimento).
- Account Ubuntu One: valutare l'aggiunta di email di team aggiuntive e la verifica dell'indirizzo
  principale.
