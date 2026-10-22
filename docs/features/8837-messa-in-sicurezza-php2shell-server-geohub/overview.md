> Ticket: oc:8837

# Messa in sicurezza PHP2Shell (CVE-2026-109618/109619) sul server geohub

## Cosa cambia

Il server di produzione di geohub (VM Hetzner Cloud «prod», Ubuntu 20.04, Apache + mod_php) smette di esporre un PHP vulnerabile a PHP2Shell, restando su PHP 7.4 e pienamente funzionante:

- il pacchetto `php7.4` viene aggiornato alla build con il fix (`7.4.3-4ubuntu2.29+esm5`), resa disponibile attivando Ubuntu Pro sulla macchina (account Ubuntu One del team, nuovo; piano gratuito fino a 5 macchine);
- la configurazione viene ripulita in modo permanente (vettore `session.upload_progress` chiuso, `LimitRequestBody` allineato ai limiti esistenti — che con mod_php si applica davvero); i limiti di upload restano quelli attuali (decisione del dev). *Errata 22/10: qui si diceva che il limite operativo effettivo fosse `post_max_size 8M`, letto da CLI; la verifica via web ha mostrato il valore reale, 50M — vedi notes.md, Task 5*;
- l'app secondaria Geomixer2 (container Docker `phpfpm:8.1`, altro PHP vulnerabile sulla stessa macchina) viene **disattivata in modo permanente**: stop + `--restart=no` + chiusura di ciò che la espone; i container restano sulla macchina come evidenza, nulla viene cancellato;
- il server dev (`geohub-dev`, https://geohub.dev.maphub.it, geohub in container `wm-phpfpm:7.4-fpm` senza possibilità di patch) viene **spento** allo stesso modo (stop + `--restart=no`), con riaccensione decisa dal dev;
- le evidenze dello stato pre-intervento vengono raccolte e copiate fuori dalla macchina, come materiale per l'attività successiva di verifica.

## Perché

Il 21/10/2026 sono state pubblicate due CVE critiche del motore PHP («PHP2Shell», CVSS 9.2): RCE non autenticata, exploit pubblico, attacchi già osservati in rete. La strategia condivisa è nel tag `php2shell` (706): un ticket per server. Prod espone PHP 7.4.3 senza fix (verificato via SSH il 22/10).

A differenza degli altri shard, qui **non si fa l'upgrade major**: il server sarà sostituito da maphub entro ~6 mesi (decisione del dev, 22/10), quindi la messa in sicurezza avviene sul ramo 7.4, per il quale Canonical ha pubblicato la build patchata (verificato sul tracker Ubuntu della CVE). **Riesame: agosto 2027** — se maphub non ha ancora sostituito geohub, questo quadro si riapre (il percorso upgrade è documentato: tetto 8.3 imposto da `nette/schema`).

## Requisiti

- [ ] Snapshot Hetzner Cloud di prod **prima di qualsiasi modifica** (ordine vincolante del tag: snapshot → evidenze → patch)
- [ ] Evidenze raccolte e copiate **fuori dalla macchina** prima di patch, stop e riavvii: **tutte le rotazioni di log disponibili** (Apache e sistema, non solo i giorni recenti), processi, cron, utenti, file recenti nelle directory scrivibili, dump DB se praticabile; il materiale viene referenziato nel ticket per l'attività successiva di verifica
- [ ] Account Ubuntu One del team creato (email di team, non personale) e verifica degli slot Pro disponibili **prima** di pianificare l'attach
- [ ] Ubuntu Pro attached su prod; **simulazione** (`apt-get install --simulate`) prima dell'installazione reale, installando **solo** i pacchetti PHP (la macchina ha 98 aggiornamenti arretrati: nessun upgrade generale)
- [ ] `php7.4` aggiornato a `7.4.3-4ubuntu2.29+esm5`
- [ ] Mitigazioni permanenti nella config Apache/mod_php di prod: `session.upload_progress.enabled=off` come `php_admin_flag` a livello server (mai `ini_set`/`.user.ini`: deve valere prima del parsing della richiesta); `LimitRequestBody` allineato ai limiti esistenti; **limiti di upload invariati**; riavvio completo di Apache
- [ ] Verifica della versione e dei valori **attraverso il web server** (phpinfo temporaneo dietro auth, con **rimozione come step esplicito e verificato**), mai dalla CLI (trappola verificata in oc:8833)
- [ ] Verifica funzionale dopo ogni passo: home, login Nova, un upload reale, POST oversize → rifiuto pulito
- [ ] Geomixer2 disattivata in modo permanente: `docker stop` + `docker update --restart=no` (mai `down`/`rm`: i container restano come evidenza) + chiusura di vhost/porta che la espongono
- [ ] dev.geohub spento allo stesso modo (stop + `--restart=no` dei container geohub, o vhost disabilitato); **spegnimento comunicato al team** prima di eseguirlo (demo o integrazioni che puntano a dev)
- [ ] Smoke-check finale documentato **nel ticket** (non nel repo): i comandi `curl` per verificare nel tempo che versione non esposta, POST oversize rifiutato, Geomixer2 e dev non raggiungibili
- [ ] Host prod aggiunto al censimento di oc:8835 (Ubuntu 20.04.4 fuori supporto; con Pro attached riceve ESM fino al 2030, e la macchina si spegne con l'arrivo di maphub)

## Rischi

- **CLI ≠ SAPI**: i valori letti da shell non sono quelli di Apache; ogni verifica si fa attraverso il web (trappola vissuta in oc:8833).
- **Lo snapshot non è uno stato fidato**: contiene l'eventuale compromissione; è solo il punto di ritorno operativo. **Il rollback complessivo coincide con una ri-esposizione** (rimette online il PHP vulnerabile sotto scansione attiva): per questo ogni step del piano è reso reversibile singolarmente, e il restore dello snapshot è l'ultima risorsa.
- **apt su macchina arretrata**: l'installazione della patch può trascinare dipendenze (98 aggiornamenti pendenti, verificato); mitigato con simulazione pre-install e installazione dei soli pacchetti PHP.
- **`restart=always`**: i container di prod ripartono da soli al reboot (verificato con `docker inspect`); mitigato con `docker update --restart=no` dopo lo stop.
- **Direttiva nel posto sbagliato**: in mod_php una direttiva messa solo nel `php.ini` può essere sovrascritta dall'app via `ini_set`; le mitigazioni vanno come `php_admin_*` nella config Apache e si verificano funzionalmente.
- **Falsi positivi nei log**: migliaia di 5xx non sono per forza attacco (su forestas ~16.600 erano un bug applicativo noto); chi analizzerà le evidenze verifichi l'URI prima di trattarli da incidente.

## Out of scope

- **Verifica di compromissione approfondita e rotazione dei segreti**: passano a una **seconda attività** gestita dal dev (decisione del 22/10). Questo ticket raccoglie e consegna le evidenze; l'analisi, l'eventuale escalation (rebuild) e la rotazione delle credenziali avvengono lì. Il `.env` di prod contiene numerosi segreti con effetto esterno (AWS, DB esterni `OUT_SOURCE_*`, JWT, mail): l'elenco dei nomi è stato rilevato il 22/10 ed è disponibile per quell'attività.
- **Upgrade PHP major (8.2/8.3)**: scartato consapevolmente — il server sarà sostituito da maphub entro ~6 mesi; riesame fissato ad agosto 2027.
- **Modifica dei limiti di upload**: restano i valori attuali per decisione del dev.
- **`old.geohub`**: macchina separata (verificato: IP diverso da prod), esclusa dal perimetro su decisione del dev.
- **Patch o migrazione dell'app Geomixer2**: si disattiva, non si aggiorna.
- **Upgrade dell'OS host**: censito e pianificato in oc:8835.
- **Ambienti locali dei dev** (Docker `wm-phpfpm:7.4-fpm` del repo): non esposti su internet; eventuale aggiornamento dell'immagine locale segue il destino di maphub.

## Moduli toccati

Nessun codice applicativo. Gli interventi sono tutti sul server (fuori dal repo): config Apache/PHP di prod, Ubuntu Pro, container Docker (Geomixer2, dev), pannello Hetzner (snapshot). Nel repo principale (unico repo, nessun submodule) entra solo la documentazione: `docs/features/8837-messa-in-sicurezza-php2shell-server-geohub/` (overview, plan, notes).

**Nota di riservatezza**: il repo è pubblico (verificato) — nessun hostname interno, IP, path di vhost o dettaglio di configurazione del server va committato oltre a quanto già scritto qui; i dettagli operativi (smoke-check compresi) vivono nel ticket e sulle macchine (trappola segnalata da oc:8833).
