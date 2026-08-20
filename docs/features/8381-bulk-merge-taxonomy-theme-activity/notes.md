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

### Smoke Nova — pending developer
`https://geohub.webmapp.it/nova` risponde con redirect 301 → `/login` (HTTP 200). Non sono disponibili credenziali in questa sessione; **smoke UI non eseguito**. Il developer deve completare la checklist sotto dopo login Nova (locale o staging).

## Follow-up

### Checklist post-deploy (operativa)

Eseguire **prima** del merge cliente su produzione/staging:

1. **Backup/dump** DB (e snapshot storage se applicabile) — operazione irreversibile, nessun undo in codice.
2. **Merge manuale in Nova**: Theme id **136** (`itinerari-consigliati`) → Main **6** (`recommended-route`). Scegliere 6 come Main (più usato, ha traduzione `en`).
3. **Reindicizzazione Elastic manuale** di tutte le track (e altre entità) le cui pivot sono state toccate dal merge.
4. **API / client**: verificare che non restino dipendenze da `/theme/idt/itinerari-consigliati` o da id 136; i client devono usare id **6** / identifier **`recommended-route`**.

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
