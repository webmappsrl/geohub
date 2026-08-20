> Ticket: oc:8381

# Bulk Merge Taxonomy (Theme, Activity) + refactor Poi Type

## Cosa cambia

In Nova sarà disponibile un’action di **Bulk Merge** sulle taxonomy **Theme** e **Activity**, con la stessa UX migliorata applicata anche a **Poi Type** (refactor dell’action esistente).

L’operatore seleziona 2+ termini duplicati, sceglie il **Main** (Select con tutti i termini; il Main deve appartenere alla selezione — validato in handle/servizio per limitazione Nova 3), conferma: le associazioni (pivot morph) passano al Main, le righe pivot in conflitto restano solo sul Main, i termini non-Main selezionati vengono eliminati.

Il merge dei duplicati cliente “Itinerari consigliati” (id **136** → **6**) resta **manuale** in Nova dopo il deploy. Operativamente: verificare usage e scegliere come Main l’id **6** (`recommended-route`, più usato e con `en`), non 136. Dopo il merge, **reindicizzare manualmente** le track toccate (Elastic); eventuali client/API su id 136 o identifier `itinerari-consigliati` vanno su 6 / `recommended-route`.

## Perché

Su cai_parma il cliente vede due Theme “Itinerari consigliati” (id 6 `recommended-route` e 136 `itinerari-consigliati`) e non sa quale scegliere. Esiste già `BulkMergePoiType`, ma Theme/Activity non hanno tool equivalente. Generalizzare il merge riduce duplicati taxonomy e evita lavoro one-shot fragile.

## Requisiti

- [x] Core merge in servizio/action parametrizzata (pivot table + FK column), riusata da Theme, Activity e Poi Type
- [x] Refactor di `BulkMergePoiType`: stessa logica/UX del generico (non lasciare due implementazioni divergenti)
- [x] Select “Main” elenca tutti i termini; il Main deve essere tra i **modelli selezionati** (minimo 2 selezionati; enforcement in handle/servizio — Nova 3 non espone la selezione in `fields()`)
- [x] Se un’entità ha già Main e duplicato sulla pivot: tenere solo l’associazione al Main, eliminare quella del duplicato (senza copia duration Activity)
- [x] Aggiornare le pivot corrette: `taxonomy_themeables`, `taxonomy_activityables`, `taxonomy_poi_typeables`
- [x] Escludere il Main dalla delete; eliminare solo i termini selezionati non-Main
- [x] Transazione DB + messaggio successo/errore Nova
- [x] Conferma Nova esplicita prima del merge (Main id/identifier + quanti termini verranno eliminati) — operazione irreversibile
- [x] Test automatici sul merge (remap pivot, conflitto pivot, delete non-Main, Activity con duration invariate sul Main)
- [x] Smoke manuale in Nova dopo implementazione (Theme, con caso reale 136→6; Activity/Poi Type non ancora verificati)
- [x] Out of scope esplicito: Target, When, Where; merge dati 136→6 non automatizzato in questo ticket

## Rischi

- **Data loss su delete taxonomy**: mitigato da select Main solo tra selezionati + esclusione Main dalla delete + transazione + conferma Nova (Main e conteggio delete); resta irreversibile senza soft-delete/undo (accettato)
- **Duplicati pivot senza unique index**: mitigato dalla policy di conflitto (delete riga duplicato, keep Main)
- **Activity duration su pivot**: colonne **usate** (non dead) — stanno su `taxonomy_activityables`, non sul detail Nova della Taxonomy Activity; espongono in API `duration.hiking` / `duration.cycling` da `EcTrack`. In conflitto pivot (stessa track con main+duplicato) non si copiano: resta la riga Main, si perde solo la duration della riga eliminata. Rischio basso (oggi zero Activity duplicate per nome; caso cliente è Theme)
- **API / identifier / Elastic**: delete del non-Main spezza riferimenti a id/identifier cancellati (`/theme/idt/{identifier}`); ES indexa themes per identifier. Mitigazione: nota operativa (Main 6) + reindicizzazione **manuale** post-merge. Reindex automatico out of scope
- **Impact morph cross-modello**: Theme è anche su App/Layer/EcMedia — il merge rimappa tutte le pivot `taxonomy_themeables`, non solo le track del caso cliente
- **Permessi**: allineati all’action Poi Type attuale (nessun `canSee` dedicato); di fatto delete taxonomy tipicamente Admin
- **Solo pivot, non metadata**: name/icon/description/feature_image del non-Main si perdono; non c’è merge di contenuti
- **Ricreazione da sync/import**: OutSource/WP o sync per identifier possono ricreare il termine eliminato e riattaccare associazioni
- **Rollback**: revert del deploy ripristina solo il codice; i merge già eseguiti sui dati non si annullano. Prima di merge critici: backup/dump operativo (non in codice)

## Out of scope

- Taxonomy **Where**, **When**, **Target**
- Merge dati automatico 136→6 (e 99/216 “Cammino di San Nilo”)
- UI custom oltre ai campi Nova Action
- Unique constraint DB sulle pivot
- Reindicizzazione Elastic automatica post-merge (va fatta manualmente)
- Copia/merge delle duration Activity in caso di conflitto pivot (le duration restano solo sulla riga Main)
- Merge/preview dei metadata taxonomy (name i18n, icon, feature_image, description)
- Protezione anti-ricreazione da sync/import OutSource/WP

## Moduli toccati

Repo: **geohub** (custom)

- `app/Nova/Actions/BulkMergePoiType.php` → thin wrapper o rimozione a favore del generico
- `app/Nova/Actions/BulkMergeTaxonomy.php` (o nome equivalente) — action Nova parametrizzata
- eventuale `app/Services/` (o simile) — core merge (pivot table + FK), se separato dall’action
- `app/Nova/TaxonomyTheme.php` — registrazione action
- `app/Nova/TaxonomyActivity.php` — registrazione action
- `app/Nova/TaxonomyPoiType.php` — punta all’action refactored
- `tests/Feature/` o `tests/Unit/` — test merge Theme / Activity (duration Main invariate) / Poi Type
