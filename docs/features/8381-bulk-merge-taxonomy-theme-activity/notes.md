> Ticket: oc:8381

# Notes — Bulk Merge Taxonomy (Theme, Activity) + refactor Poi Type

## Deviazioni dal piano

### Task 1 — helper `createEcTrack()` con `forceCreate`
Il piano usava `EcTrack::factory()->create()` nei test. In ambiente locale la factory falliva (validazione / side-effect su `EcTrack`). Introdotto helper privato `createEcTrack()` in `tests/Feature/TaxonomyBulkMergeServiceTest.php` che usa `EcTrack::forceCreate([...])` con `user_id` da factory, `geometry` PostGIS e `refresh()` — sufficiente per inserire pivot morph senza dipendere dalla factory completa.

### Nova 3 — Select Main non filtrabile per selezione (option A)
In Nova 3, `fields()` viene serializzato su GET `/actions` **senza** gli ID selezionati: `request('resources')` è vuoto. Non è possibile limitare le opzioni del Select ai soli termini selezionati.

**Decisione (option A):** il Select elenca **tutti** i termini della taxonomy (`name (identifier) [#id]`, come il vecchio `BulkMergePoiType`). La validazione che il Main sia tra i **modelli selezionati** resta in `handle()` / `TaxonomyBulkMergeService::merge()` (Nova passa `$models` al momento dell’esecuzione). Heading e `confirmText` non affermano che le opzioni sono solo i selezionati.

### Task 3 — rimozione `BulkMergePoiType`
Eliminato `app/Nova/Actions/BulkMergePoiType.php` (nessun thin wrapper). `TaxonomyPoiType::actions()` punta direttamente a `BulkMergeTaxonomy` con label **Bulk Merge Poi Type**.

### Task 1 — risoluzione conflitto pivot con `whereRaw` a tupla
Il piano proponeva di risolvere il conflitto pivot con un doppio loop (`SELECT` delle righe Main + `DELETE` riga per riga sul duplicato). L'implementazione usa invece una singola query con `whereRaw("({$morphIdColumn}, {$morphTypeColumn}) IN (SELECT ... WHERE {$foreignKey} = ?)")`. Semanticamente equivalente (stessa policy: keep Main, delete duplicato in conflitto), ma sintassi row-value `IN (SELECT ...)` valida su PostgreSQL/MySQL ≥8.0.19, non su SQLite/MySQL più vecchio — non rilevante in questo progetto (`DB_CONNECTION=pgsql` sia in produzione che nei test, `RefreshDatabase` usa lo stesso driver).

**Nota su revisione post-implementazione:** in fase di code review è stata aggiunta una validazione di schema in `TaxonomyBulkMergeService::merge()` (`Schema::hasTable`/`Schema::hasColumn`) che lancia `InvalidArgumentException` con messaggio chiaro se `pivotTable`/colonne passate non esistono, invece di un errore SQL generico a runtime. Aggiunto anche un test per il caso di 3+ modelli selezionati con conflitto a catena (comportamento order-dependent ma senza corruzione dati: la riga pivot duplicata rimane comunque unica sul Main).

## Bug trovati

Nessuno emerso in questa sessione (Task 4). I test automatici del servizio non sono stati rieseguiti in Task 4: `php artisan test` fallisce in locale per `redis:6379` non raggiungibile (Telescope/bootstrap), non per errori nel codice del merge.

## Decisioni

### Verifica statica registrazione action (Task 4)
Confermato via `rg BulkMergeTaxonomy app/Nova/Taxonomy*.php`:

| Resource Nova | Action label | File |
|---------------|--------------|------|
| Taxonomy Theme | Bulk Merge Theme | `app/Nova/TaxonomyTheme.php` |
| Taxonomy Activity | Bulk Merge Activity | `app/Nova/TaxonomyActivity.php` |
| Taxonomy Poi Type | Bulk Merge Poi Type | `app/Nova/TaxonomyPoiType.php` |

Nessun riferimento residuo a `BulkMergePoiType` in `app/` o `tests/` (solo menzioni in docs del piano).

### Smoke Nova — Theme, eseguito dal developer (merge produzione 136→6)
Il developer ha eseguito manualmente in Nova l'azione **Bulk Merge Theme** sul caso reale del ticket: Theme id **136** (`itinerari-consigliati`) selezionato insieme al **6** (`recommended-route`), Main = **6**. Verifica programmatica post-merge (via tinker):

- `TaxonomyTheme::find(136)` → `null` (eliminato); `TaxonomyTheme::find(6)` → presente, identifier `recommended-route`, name "Itinerari consigliati"
- `taxonomy_themeables` con `taxonomy_theme_id = 136` → 0 righe residue
- `taxonomy_themeables` con `taxonomy_theme_id = 6` → 56 righe totali, nessun morph target duplicato (query di `GROUP BY` + `HAVING count(*) > 1` → 0 risultati)
- Breakdown per tipo morph: `EcTrack` 53, `EcMedia` 1, `EcPoi` 1, `Layer` 1 (coerente col rischio "impact cross-modello" in `overview.md`)
- Nessun errore in `storage/logs/laravel.log` per `BulkMergeTaxonomy`/`merge failed` (il logging aggiunto in review non si è attivato)

Esito: **merge Theme 136→6 completato con successo**, dati coerenti, nessuna corruzione pivot. Questo esegue anche lo step 2 della Checklist post-deploy sotto.

Smoke non ancora eseguito su **Activity** e **Poi Type** (solo Theme verificato, con il caso reale invece di termini di test).

## Follow-up

### Checklist post-deploy (operativa)

Eseguire **prima** del merge cliente su produzione/staging:

1. **Backup/dump** DB (e snapshot storage se applicabile) — operazione irreversibile, nessun undo in codice. _Stato non confermato in questa sessione._
2. ✅ **Eseguito** — **Merge manuale in Nova**: Theme id **136** (`itinerari-consigliati`) → Main **6** (`recommended-route`). Verificato programmaticamente (vedi "Smoke Nova — Theme" sopra): 56 pivot rimappate, 0 duplicati, 0 errori.
3. ⏳ **Ancora da fare** — **Reindicizzazione Elastic manuale** delle 53 `EcTrack` (+ 1 `EcMedia`, 1 `EcPoi`, 1 `Layer`) le cui pivot sono state toccate dal merge.
4. ⏳ **Ancora da fare** — **API / client**: verificare che non restino dipendenze da `/theme/idt/itinerari-consigliati` o da id 136; i client devono usare id **6** / identifier **`recommended-route`**.

### Smoke checklist developer (Nova)

Usare **termini di test** (2+ duplicati), non i dati produzione finché non si passa al merge operativo sopra.

**Per ciascuna resource** (Theme, Activity, Poi Type):

- [ ] Index resource → seleziona **2 termini di test**
- [ ] Actions → **Bulk Merge Theme** / **Bulk Merge Activity** / **Bulk Merge Poi Type**
- [ ] Select Main: opzioni = **tutti i termini** (label `nome (identifier) [#id]`); Main scelto deve essere uno dei selezionati (validato a runtime)
- [ ] Heading: testo irreversibile + Main deve essere tra i selezionati (no conteggio delete in `fields()`)
- [ ] Dialog conferma: testo irreversibile (`confirmText` + Heading HTML)
- [ ] Esegui merge → messaggio successo Nova
- [ ] DB: pivot rimappate sul Main; termine non-Main eliminato dalla tabella taxonomy
- [ ] (Theme) Conflitto pivot: stessa entità con main+duplicato → resta solo riga Main

**EcTrack nei test (riferimento Task 1)**

I test del servizio non usano la factory EcTrack; usano `forceCreate` con geometry PostGIS. Non impatta Nova smoke, ma spiega perché i test locali possono comportarsi diversamente da altri test feature che usano `EcTrack::factory()`.
