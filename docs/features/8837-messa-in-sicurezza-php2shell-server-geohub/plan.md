> Ticket: oc:8837

# Messa in sicurezza PHP2Shell sul server geohub — Implementation Plan

> **For agentic workers:** piano operativo su server via SSH, non eseguibile da subagenti: ogni azione sul server richiede la conferma del dev al momento dell'esecuzione. Gli step usano checkbox (`- [ ]`) per il tracking.

**Goal:** chiudere la CVE PHP2Shell sul server di produzione di geohub mantenendo PHP 7.4 e il sito funzionante, e azzerare le altre superfici PHP vulnerabili (Geomixer2, dev).

**Architettura:** interventi solo lato server (nessun codice applicativo): snapshot → evidenze → patch ESM → mitigazioni Apache → disattivazioni permanenti → verifiche. Ogni task è reversibile singolarmente; il restore dello snapshot è l'ultima risorsa (= ri-esposizione).

**Tech stack:** Ubuntu 20.04, Apache + mod_php 7.4, Ubuntu Pro/ESM, Docker (solo per i container da fermare), Hetzner Cloud.

**Spec:** `docs/features/8837-messa-in-sicurezza-php2shell-server-geohub/overview.md`

## Global Constraints

- Ordine vincolante: snapshot → evidenze → ogni altra modifica (nessuno stop di container prima delle evidenze).
- Solo i pacchetti PHP si installano: mai `apt upgrade` generale (98 aggiornamenti arretrati sulla macchina).
- Ogni verifica di configurazione PHP passa **dal web server** (CLI ≠ SAPI).
- Mai `docker compose down`, `docker rm`, cancellazioni: i container fermati sono evidenze.
- Limiti di upload **invariati** (decisione dev); `JWT_SECRET` e rotazione segreti fuori scope (seconda attività).
- Repo pubblico: in questo file e nei commit nessun IP, credenziale o dettaglio fine di config; i dettagli operativi vivono nel ticket oc:8837.
- Ogni comando sul server viene mostrato al dev e eseguito solo dopo il suo ok.

## Review Focus

1. Dopo la patch, un utente carica un file come oggi (≤8M effettivi): deve funzionare identico — coperto dal test funzionale del Task 4.
2. Un POST oversize (>LimitRequestBody) deve morire con un errore pulito, non con un worker ucciso — Task 5.
3. Un reboot della macchina non deve riaccendere Geomixer2 né i container di dev — Task 6/7 (verifica `--restart=no`).
4. Il phpinfo temporaneo non deve sopravvivere alla fine del lavoro — Task 4, step di rimozione verificata.
5. Chi analizzerà le evidenze (seconda attività) deve trovare materiale integro e completo — Task 2, verifica del contenuto copiato.

---

### Task 1: Snapshot Hetzner Cloud di prod

**Interfaces — Produces:** snapshot pre-intervento, punto di ritorno operativo per tutti i task successivi.

- [ ] **Step 1:** dal pannello Hetzner Cloud, creare lo snapshot della VM di prod (nome: `pre-oc8837-<data>`). Azione manuale del dev o guidata; la VM resta accesa.
- [ ] **Step 2:** verificare nel pannello che lo snapshot risulti completato prima di procedere.
Expected: snapshot in stato «available».

### Task 2: Raccolta evidenze fuori macchina

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-2-raccolta-evidenze-sostituita-dallo-snapshot)

**Interfaces — Produces:** archivio evidenze su macchina locale, referenziato nel ticket; prerequisito per ogni modifica successiva.

- [ ] **Step 1:** su prod, raccogliere in un tar (in una dir temporanea, sola lettura delle fonti): **tutte** le rotazioni dei log Apache e di sistema (`/var/log/apache2/`, `auth.log*`, `syslog*`), elenco processi (`ps auxf`), cron di tutti gli utenti (`/etc/cron*`, `crontab -l` per utente), utenti e ultimi accessi (`/etc/passwd`, `last`), file modificati di recente nelle directory scrivibili dal web (`find /var/www /tmp -mtime -30 -type f`).
- [ ] **Step 2:** dump del DB di geohub (il Postgres gira nel container `postgres_Geohub`): `docker exec postgres_Geohub pg_dump ...` se praticabile senza impatti; altrimenti annotare il motivo in notes.md.
- [ ] **Step 3:** copiare l'archivio **fuori dalla macchina** via `scp` sulla macchina locale del dev.
- [ ] **Step 4:** verificare l'integrità della copia (dimensioni/`tar -t`) e cancellare il tar temporaneo dal server (solo il tar creato da noi, non le fonti).
Expected: archivio locale leggibile; posizione annotata nel ticket (campo note dev, a fine lavoro).

### Task 3: Ubuntu Pro attach

**Interfaces — Consumes:** Task 1–2 completati. **Produces:** apt vede il canale ESM con `php7.4 2.29+esm5`.

- [ ] **Step 1:** creare l'account Ubuntu One con un'email di team (non personale); recuperare il token dal pannello Pro e verificare gli slot gratuiti disponibili (5 totali, account nuovo = 5 liberi).
- [ ] **Step 2:** su prod: `sudo pro attach <token>` (se `pro` manca: prima `apt install ubuntu-advantage-tools`, solo quel pacchetto).
- [ ] **Step 3:** verifica: `pro status` deve mostrare `esm-infra: enabled`; `apt-cache policy php7.4` deve mostrare il candidato `7.4.3-4ubuntu2.29+esm5`.
Expected: candidato `+esm5` visibile.
Reversibilità: `pro detach` (non tocca i pacchetti già installati).

### Task 4: Patch php7.4

**Interfaces — Consumes:** Task 3. **Produces:** PHP patchato in esecuzione sotto Apache.

- [ ] **Step 1:** simulazione: `apt-get install --simulate libapache2-mod-php7.4 php7.4 'php7.4-*'` limitata ai pacchetti php7.4 **già installati** (elenco da `dpkg -l 'php7.4*'`). Leggere l'elenco di ciò che verrebbe toccato: se compaiono pacchetti non-PHP rilevanti (apache2, libssl), fermarsi e portare l'elenco al dev.
- [ ] **Step 2:** installazione reale dei soli pacchetti approvati; riavvio completo di Apache (`systemctl restart apache2`).
- [ ] **Step 3:** verifica **via web**: phpinfo temporaneo dietro basic auth (file in una path non indovinabile) → la versione riportata deve essere `7.4.3-4ubuntu2.29+esm5`; in alternativa `dpkg -l php7.4` + header di risposta.
- [ ] **Step 4:** verifica funzionale: home 200, login Nova, un upload reale andato a buon fine.
- [ ] **Step 5:** **rimozione verificata** del phpinfo temporaneo: `rm` + richiesta web che deve rispondere 404.
Expected: versione `+esm5` via SAPI web; sito funzionante; phpinfo assente.
Reversibilità: `apt install php7.4=7.4.3-4ubuntu2.29 ...` (downgrade esplicito) oppure snapshot — entrambi riaprono la CVE, da usare solo su rottura grave.

### Task 5: Mitigazioni nella config Apache/mod_php

**Interfaces — Consumes:** Task 4. **Produces:** vettore upload_progress chiuso, contenimento richieste oversize.

- [ ] **Step 1:** nella config Apache a livello server (conf dedicata, non nei vhost): `php_admin_flag session.upload_progress.enabled off` e `LimitRequestBody` allineato ai limiti esistenti (coerente con `post_max_size 8M` effettivo, con margine; valore esatto deciso col dev al momento). Limiti upload **invariati**.
- [ ] **Step 2:** `apachectl configtest` → `Syntax OK`; riavvio completo di Apache.
- [ ] **Step 3:** verifica via web (phpinfo temporaneo dietro auth, poi rimosso con 404 verificato): `session.upload_progress.enabled => Off`.
- [ ] **Step 4:** verifica funzionale: home, login Nova, upload reale ok; POST oversize → rifiuto pulito (413/400), worker Apache vivi.
Expected: Off via SAPI; sito funzionante; oversize respinto.
Reversibilità: rimozione della conf dedicata + riavvio.

### Task 6: Disattivazione permanente di Geomixer2

**Interfaces — Consumes:** Task 1–2 (evidenze già raccolte). **Produces:** seconda superficie PHP di prod azzerata.

- [ ] **Step 1:** individuare cosa espone Geomixer2 (vhost Apache o porta pubblicata del container) — sola lettura.
- [ ] **Step 2:** `docker stop php81_Geomixer2 mariadb_Geomixer2` + `docker update --restart=no` sugli stessi container (mai `down`/`rm`).
- [ ] **Step 3:** disabilitare il vhost/chiudere l'esposizione individuata allo Step 1; riavvio Apache se toccato.
- [ ] **Step 4:** verifica: l'URL/porta di Geomixer2 non risponde più; `docker inspect` mostra `restart=no`; geohub funziona (home 200).
Expected: Geomixer2 irraggiungibile, container presenti ma fermi anche a fronte di reboot.
Reversibilità: `docker update --restart=always` + `docker start` + riabilitazione vhost.

### Task 7: Spegnimento di dev.geohub

**Interfaces — Consumes:** decisione dev già presa; comunicazione al team. **Produces:** superficie dev azzerata.

- [ ] **Step 1:** comunicare al team lo spegnimento di `geohub.dev.maphub.it` (demo o integrazioni che ci puntano) e attendere l'ok del dev a procedere.
- [ ] **Step 2:** su dev: `docker stop` dei container geohub + `docker update --restart=no` (stesso schema del Task 6).
- [ ] **Step 3:** verifica: https://geohub.dev.maphub.it non serve più l'app (errore/504 da Apache, o vhost disabilitato se si preferisce una pagina pulita); `restart=no` su tutti i container fermati.
Expected: dev irraggiungibile, riaccendibile a comando.
Reversibilità: `docker update --restart=always` + `docker start`.

### Task 8: Smoke-check, censimento e chiusura documentale

**Interfaces — Consumes:** Task 4–7. **Produces:** verifiche ripetibili nel ticket, censimento oc:8835 aggiornato, artefatti nel repo.

- [ ] **Step 1:** scrivere **nel ticket oc:8837** (note dev, non nel repo) i comandi `curl` dello smoke-check: versione non esposta negli header, POST oversize → rifiuto, URL Geomixer2 e dev non raggiungibili. Eseguirli una volta e registrare l'esito.
- [ ] **Step 2:** valutare con il dev `expose_php = Off` (oggi l'header `x-powered-by` dichiara la versione): una riga nella stessa conf del Task 5, riavvio, verifica che l'header sparisca.
- [ ] **Step 3:** aggiungere l'host prod al censimento di oc:8835 (`update_story` con `prepend`, anteprima + conferma): Ubuntu 20.04.4, Pro attached, ESM fino al 2030, macchina destinata a spegnersi con maphub.
- [ ] **Step 4:** completare `notes.md` (decisioni: stima saltata, limiti invariati, old.geohub escluso, seconda attività) e verificare la checklist di completamento del workflow.
- [ ] **Step 5:** commit della sola documentazione, **dopo approvazione esplicita del dev**: `feat(oc:8837): add security hardening docs for PHP2Shell` (istruzione testuale: nessun commit senza il sì del dev).
Expected: smoke-check nel ticket con esito, censimento aggiornato, tre file in `docs/features/8837-messa-in-sicurezza-php2shell-server-geohub/`.
