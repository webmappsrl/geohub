> Ticket: oc:8381

# Bulk Merge Taxonomy (Theme, Activity) + refactor Poi Type — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **⚠️ Webmapp override:** non eseguire `git commit` / `git add` / `git push` durante l’implementazione. I blocchi “Commit” sotto sono istruzioni testuali per il developer dopo il review-gate. Scrivi solo i file.

**Goal:** Fornire un merge taxonomy riusabile (Theme, Activity, Poi Type) con Main solo tra selezionati, risoluzione conflitti pivot (keep Main), conferma irreversibile, e test automatici.

**Architecture:** Core in `App\Services\TaxonomyBulkMergeService` (pivot table + FK + colonne morph). Action Nova `BulkMergeTaxonomy` parametrizzata per resource. `BulkMergePoiType` rimosso o ridotto a thin wrapper deprecato. Nessuna migration.

**Tech Stack:** Laravel 8, Nova Actions, PHPUnit 9, factories taxonomy esistenti.

## Global Constraints

- Repo: **geohub** (custom) — nessun submodule
- Scope: Theme + Activity + refactor Poi Type (non Where/When/Target)
- Main select: solo modelli selezionati; minimo 2 selezionati
- Conflitto pivot: keep Main, delete riga duplicato; **non** copiare duration Activity
- Conferma Nova esplicita (Main id/identifier + conteggio delete)
- Reindex Elastic e merge 136→6: **manuali**, fuori codice
- Commit message scope: `oc:8381` (es. `feat(oc:8381): ...`) — solo dopo review-gate
- Test runner: `php artisan test` (o via Docker se disponibile)

## File map

| File | Responsabilità |
|------|----------------|
| Create: `app/Services/TaxonomyBulkMergeService.php` | Logica merge in transazione |
| Create: `app/Nova/Actions/BulkMergeTaxonomy.php` | Action Nova parametrizzata + fields/confirm |
| Modify: `app/Nova/TaxonomyTheme.php` | Registra action |
| Modify: `app/Nova/TaxonomyActivity.php` | Registra action |
| Modify: `app/Nova/TaxonomyPoiType.php` | Usa action generica |
| Delete: `app/Nova/Actions/BulkMergePoiType.php` | Sostituita (nessun wrapper se non serve) |
| Create: `tests/Feature/TaxonomyBulkMergeServiceTest.php` | Test core merge |

### Config pivot per taxonomy

| Model | Pivot | FK | Morph id | Morph type |
|-------|-------|-----|----------|------------|
| `TaxonomyTheme` | `taxonomy_themeables` | `taxonomy_theme_id` | `taxonomy_themeable_id` | `taxonomy_themeable_type` |
| `TaxonomyActivity` | `taxonomy_activityables` | `taxonomy_activity_id` | `taxonomy_activityable_id` | `taxonomy_activityable_type` |
| `TaxonomyPoiType` | `taxonomy_poi_typeables` | `taxonomy_poi_type_id` | `taxonomy_poi_typeable_id` | `taxonomy_poi_typeable_type` |

---

### Task 1: TaxonomyBulkMergeService (TDD)

**Files:**
- Create: `tests/Feature/TaxonomyBulkMergeServiceTest.php`
- Create: `app/Services/TaxonomyBulkMergeService.php`

**Interfaces:**
- Consumes: Eloquent models + `DB` facade; factories `TaxonomyTheme`, `TaxonomyActivity`, `TaxonomyPoiType`, `EcTrack`
- Produces: `TaxonomyBulkMergeService::merge(Collection $models, int $mainId, string $pivotTable, string $foreignKey, string $morphIdColumn, string $morphTypeColumn): void` — throws `\InvalidArgumentException` se main non è tra i models o models &lt; 2

- [x] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature;

use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTheme;
use App\Services\TaxonomyBulkMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaxonomyBulkMergeServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxonomyBulkMergeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaxonomyBulkMergeService();
    }

    public function test_theme_merge_remaps_pivot_and_deletes_duplicates(): void
    {
        $main = TaxonomyTheme::factory()->create(['identifier' => 'main-theme']);
        $dup = TaxonomyTheme::factory()->create(['identifier' => 'dup-theme']);
        $track = EcTrack::factory()->create();

        DB::table('taxonomy_themeables')->insert([
            'taxonomy_theme_id' => $dup->id,
            'taxonomy_themeable_id' => $track->id,
            'taxonomy_themeable_type' => EcTrack::class,
        ]);

        $this->service->merge(
            collect([$main, $dup]),
            $main->id,
            'taxonomy_themeables',
            'taxonomy_theme_id',
            'taxonomy_themeable_id',
            'taxonomy_themeable_type'
        );

        $this->assertDatabaseMissing('taxonomy_themes', ['id' => $dup->id]);
        $this->assertDatabaseHas('taxonomy_themes', ['id' => $main->id]);
        $this->assertDatabaseHas('taxonomy_themeables', [
            'taxonomy_theme_id' => $main->id,
            'taxonomy_themeable_id' => $track->id,
            'taxonomy_themeable_type' => EcTrack::class,
        ]);
        $this->assertDatabaseMissing('taxonomy_themeables', [
            'taxonomy_theme_id' => $dup->id,
        ]);
    }

    public function test_theme_merge_keeps_main_pivot_on_conflict(): void
    {
        $main = TaxonomyTheme::factory()->create();
        $dup = TaxonomyTheme::factory()->create();
        $track = EcTrack::factory()->create();

        DB::table('taxonomy_themeables')->insert([
            [
                'taxonomy_theme_id' => $main->id,
                'taxonomy_themeable_id' => $track->id,
                'taxonomy_themeable_type' => EcTrack::class,
            ],
            [
                'taxonomy_theme_id' => $dup->id,
                'taxonomy_themeable_id' => $track->id,
                'taxonomy_themeable_type' => EcTrack::class,
            ],
        ]);

        $this->service->merge(
            collect([$main, $dup]),
            $main->id,
            'taxonomy_themeables',
            'taxonomy_theme_id',
            'taxonomy_themeable_id',
            'taxonomy_themeable_type'
        );

        $rows = DB::table('taxonomy_themeables')
            ->where('taxonomy_themeable_id', $track->id)
            ->where('taxonomy_themeable_type', EcTrack::class)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertEquals($main->id, $rows->first()->taxonomy_theme_id);
    }

    public function test_activity_merge_preserves_main_pivot_durations_on_conflict(): void
    {
        $main = TaxonomyActivity::factory()->create();
        $dup = TaxonomyActivity::factory()->create();
        $track = EcTrack::factory()->create();

        DB::table('taxonomy_activityables')->insert([
            [
                'taxonomy_activity_id' => $main->id,
                'taxonomy_activityable_id' => $track->id,
                'taxonomy_activityable_type' => EcTrack::class,
                'duration_forward' => 100,
                'duration_backward' => 90,
            ],
            [
                'taxonomy_activity_id' => $dup->id,
                'taxonomy_activityable_id' => $track->id,
                'taxonomy_activityable_type' => EcTrack::class,
                'duration_forward' => 50,
                'duration_backward' => 40,
            ],
        ]);

        $this->service->merge(
            collect([$main, $dup]),
            $main->id,
            'taxonomy_activityables',
            'taxonomy_activity_id',
            'taxonomy_activityable_id',
            'taxonomy_activityable_type'
        );

        $row = DB::table('taxonomy_activityables')
            ->where('taxonomy_activityable_id', $track->id)
            ->where('taxonomy_activity_id', $main->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertEquals(100, $row->duration_forward);
        $this->assertEquals(90, $row->duration_backward);
        $this->assertDatabaseMissing('taxonomy_activities', ['id' => $dup->id]);
    }

    public function test_poi_type_merge_remaps_pivot(): void
    {
        $main = TaxonomyPoiType::factory()->create();
        $dup = TaxonomyPoiType::factory()->create();
        $track = EcTrack::factory()->create();

        DB::table('taxonomy_poi_typeables')->insert([
            'taxonomy_poi_type_id' => $dup->id,
            'taxonomy_poi_typeable_id' => $track->id,
            'taxonomy_poi_typeable_type' => EcTrack::class,
        ]);

        $this->service->merge(
            collect([$main, $dup]),
            $main->id,
            'taxonomy_poi_typeables',
            'taxonomy_poi_type_id',
            'taxonomy_poi_typeable_id',
            'taxonomy_poi_typeable_type'
        );

        $this->assertDatabaseHas('taxonomy_poi_typeables', [
            'taxonomy_poi_type_id' => $main->id,
            'taxonomy_poi_typeable_id' => $track->id,
        ]);
        $this->assertDatabaseMissing('taxonomy_poi_types', ['id' => $dup->id]);
    }

    public function test_merge_rejects_main_not_in_selection(): void
    {
        $main = TaxonomyTheme::factory()->create();
        $dup = TaxonomyTheme::factory()->create();
        $other = TaxonomyTheme::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->merge(
            collect([$main, $dup]),
            $other->id,
            'taxonomy_themeables',
            'taxonomy_theme_id',
            'taxonomy_themeable_id',
            'taxonomy_themeable_type'
        );
    }
}
```

- [x] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=TaxonomyBulkMergeServiceTest`

Expected: FAIL (class `TaxonomyBulkMergeService` not found)

- [x] **Step 3: Implement `TaxonomyBulkMergeService`**

```php
<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TaxonomyBulkMergeService
{
    public function merge(
        Collection $models,
        int $mainId,
        string $pivotTable,
        string $foreignKey,
        string $morphIdColumn,
        string $morphTypeColumn
    ): void {
        if ($models->count() < 2) {
            throw new InvalidArgumentException('Select at least two taxonomy terms to merge.');
        }

        if (! $models->contains(fn ($model) => (int) $model->id === $mainId)) {
            throw new InvalidArgumentException("Main taxonomy id {$mainId} is not among the selected models.");
        }

        $duplicates = $models->filter(fn ($model) => (int) $model->id !== $mainId);

        DB::transaction(function () use ($duplicates, $mainId, $pivotTable, $foreignKey, $morphIdColumn, $morphTypeColumn) {
            foreach ($duplicates as $duplicate) {
                $conflictKeys = DB::table($pivotTable)
                    ->where($foreignKey, $mainId)
                    ->get([$morphIdColumn, $morphTypeColumn]);

                foreach ($conflictKeys as $key) {
                    DB::table($pivotTable)
                        ->where($foreignKey, $duplicate->id)
                        ->where($morphIdColumn, $key->{$morphIdColumn})
                        ->where($morphTypeColumn, $key->{$morphTypeColumn})
                        ->delete();
                }

                DB::table($pivotTable)
                    ->where($foreignKey, $duplicate->id)
                    ->update([$foreignKey => $mainId]);

                $duplicate->delete();
            }
        });
    }
}
```

- [x] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=TaxonomyBulkMergeServiceTest`

Expected: PASS (tutti i test verdi)

Se `EcTrack::factory()` fallisce per campi obbligatori geometria, adatta il factory state già usato in altri test Feature del repo (copia il pattern da un test EcTrack esistente).

- [ ] **Step 5: Commit (istruzione — non eseguire ora)**

```bash
git add app/Services/TaxonomyBulkMergeService.php tests/Feature/TaxonomyBulkMergeServiceTest.php
git commit -m "$(cat <<'EOF'
feat(oc:8381): add TaxonomyBulkMergeService with pivot conflict handling

EOF
)"
```

---

### Task 2: Nova action `BulkMergeTaxonomy`

**Files:**
- Create: `app/Nova/Actions/BulkMergeTaxonomy.php`
- Delete: `app/Nova/Actions/BulkMergePoiType.php` (dopo Task 3 wiring)

**Interfaces:**
- Consumes: `TaxonomyBulkMergeService::merge(...)`
- Produces: `BulkMergeTaxonomy` costruttore `(string $modelClass, string $pivotTable, string $foreignKey, string $morphIdColumn, string $morphTypeColumn, string $actionName)`

- [x] **Step 1: Implement action**

```php
<?php

namespace App\Nova\Actions;

use App\Services\TaxonomyBulkMergeService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\Select;

class BulkMergeTaxonomy extends Action
{
    use InteractsWithQueue, Queueable;

    protected string $modelClass;
    protected string $pivotTable;
    protected string $foreignKey;
    protected string $morphIdColumn;
    protected string $morphTypeColumn;
    protected string $actionName;

    public function __construct(
        string $modelClass,
        string $pivotTable,
        string $foreignKey,
        string $morphIdColumn,
        string $morphTypeColumn,
        string $actionName
    ) {
        $this->modelClass = $modelClass;
        $this->pivotTable = $pivotTable;
        $this->foreignKey = $foreignKey;
        $this->morphIdColumn = $morphIdColumn;
        $this->morphTypeColumn = $morphTypeColumn;
        $this->actionName = $actionName;

        $this->confirmText(
            'This merge is irreversible. Pivot associations move to the Main term; non-Main terms are permanently deleted. Metadata of deleted terms is not merged.'
        );
    }

    public function name()
    {
        return $this->actionName;
    }

    public function handle(ActionFields $fields, Collection $models)
    {
        if ($models->count() < 2) {
            return Action::danger('Select at least two taxonomy terms to merge.');
        }

        $mainId = (int) $fields->get('main_taxonomy');

        try {
            app(TaxonomyBulkMergeService::class)->merge(
                $models,
                $mainId,
                $this->pivotTable,
                $this->foreignKey,
                $this->morphIdColumn,
                $this->morphTypeColumn
            );
        } catch (\InvalidArgumentException $e) {
            return Action::danger($e->getMessage());
        } catch (\Throwable $e) {
            return Action::danger('Error while merging: '.$e->getMessage());
        }

        return Action::message('Merge completed successfully.');
    }

    public function fields()
    {
        $resourceIds = collect(explode(',', (string) request()->get('resources', '')))
            ->filter(fn ($id) => $id !== '' && $id !== 'all')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $selected = $this->modelClass::query()
            ->whereIn('id', $resourceIds)
            ->get();

        $deleteCount = max(0, $selected->count() - 1);

        $options = $selected->mapWithKeys(function ($model) {
            $name = is_array($model->name) && isset($model->name['it'])
                ? $model->name['it']
                : (is_string($model->name) ? $model->name : json_encode($model->name));

            return [$model->id => "{$name} ({$model->identifier}) [#{$model->id}]"];
        })->toArray();

        return [
            Heading::make(
                '<p><strong>Irreversible.</strong> Non-Main terms will be deleted: <strong>'
                .$deleteCount
                .'</strong>. Choose Main among the selected terms only.</p>'
            )->asHtml(),
            Select::make('Main taxonomy', 'main_taxonomy')
                ->options($options)
                ->displayUsingLabels()
                ->searchable()
                ->rules('required'),
        ];
    }
}
```

Note: se `request()->get('resources')` in questo stack Nova arriva in altro formato (array), normalizzare in `fields()` come fa `ActionRequest` (`explode` su stringa). Verificare nello smoke Task 4.

- [ ] **Step 2: Commit (istruzione — non eseguire ora)**

```bash
git add app/Nova/Actions/BulkMergeTaxonomy.php
git commit -m "$(cat <<'EOF'
feat(oc:8381): add parameterized BulkMergeTaxonomy Nova action

EOF
)"
```

---

### Task 3: Wire Theme, Activity, Poi Type

**Files:**
- Modify: `app/Nova/TaxonomyTheme.php` (`actions()`)
- Modify: `app/Nova/TaxonomyActivity.php` (`actions()`)
- Modify: `app/Nova/TaxonomyPoiType.php` (`actions()` + remove `use BulkMergePoiType`)
- Delete: `app/Nova/Actions/BulkMergePoiType.php`

**Interfaces:**
- Consumes: `BulkMergeTaxonomy` constructor
- Produces: action visibile nelle tre index Nova

- [x] **Step 1: Register on TaxonomyTheme**

In `actions()`:

```php
use App\Models\TaxonomyTheme as TaxonomyThemeModel;
use App\Nova\Actions\BulkMergeTaxonomy;

public function actions(Request $request)
{
    return [
        new BulkMergeTaxonomy(
            TaxonomyThemeModel::class,
            'taxonomy_themeables',
            'taxonomy_theme_id',
            'taxonomy_themeable_id',
            'taxonomy_themeable_type',
            'Bulk Merge Theme'
        ),
    ];
}
```

(Usa alias del Model se la resource si chiama già `TaxonomyTheme`.)

- [x] **Step 2: Register on TaxonomyActivity**

```php
new BulkMergeTaxonomy(
    \App\Models\TaxonomyActivity::class,
    'taxonomy_activityables',
    'taxonomy_activity_id',
    'taxonomy_activityable_id',
    'taxonomy_activityable_type',
    'Bulk Merge Activity'
),
```

- [x] **Step 3: Replace Poi Type action**

```php
new BulkMergeTaxonomy(
    \App\Models\TaxonomyPoiType::class,
    'taxonomy_poi_typeables',
    'taxonomy_poi_type_id',
    'taxonomy_poi_typeable_id',
    'taxonomy_poi_typeable_type',
    'Bulk Merge Poi Type'
),
```

Rimuovi `use App\Nova\Actions\BulkMergePoiType` e cancella il file `BulkMergePoiType.php`.

- [x] **Step 4: Grep for leftover references**

Run: `rg -n "BulkMergePoiType" app tests`

Expected: nessun match (o solo docs)

- [x] **Step 5: Re-run service tests**

Run: `php artisan test --filter=TaxonomyBulkMergeServiceTest`

Expected: PASS

- [ ] **Step 6: Commit (istruzione — non eseguire ora)**

```bash
git add app/Nova/TaxonomyTheme.php app/Nova/TaxonomyActivity.php app/Nova/TaxonomyPoiType.php
git add -u app/Nova/Actions/BulkMergePoiType.php
git commit -m "$(cat <<'EOF'
feat(oc:8381): wire BulkMergeTaxonomy on Theme, Activity, Poi Type

EOF
)"
```

---

### Task 4: Smoke manuale Nova + checklist operativa

**Files:** nessuno (verifica + note)

- [x] **Step 1: Smoke in Nova (local)** — eseguito su Theme con caso reale 136→6 (vedi notes.md); Activity/Poi Type non ancora verificati

1. Taxonomy Theme index: seleziona 2 temi di test → action **Bulk Merge Theme**
2. Verifica Select Main: solo i 2 selezionati; Heading con conteggio delete = 1
3. Conferma dialog irreversibile → merge → successo
4. Verifica DB: pivot sul Main, duplicato eliminato
5. Ripeti smoke minimo su Activity e Poi Type (2 termini di test, non produzione)

- [x] **Step 2: Checklist post-deploy (operativa, non codice)**

Documentare in `notes.md` al termine (o lasciare qui come reminder):

1. Backup/dump prima del merge critico
2. Merge 136 → Main **6** (`recommended-route`)
3. Reindicizzazione Elastic manuale delle track toccate
4. Verificare che `/theme/idt/itinerari-consigliati` non sia più necessario / client usino `recommended-route`

- [ ] **Step 3: Commit docs only se aggiorni notes in questa sessione (istruzione)**

```bash
git add docs/features/8381-bulk-merge-taxonomy-theme-activity/
git commit -m "$(cat <<'EOF'
docs(oc:8381): record smoke and post-deploy merge checklist

EOF
)"
```

---

## Spec coverage (self-review)

| Requisito overview | Task |
|--------------------|------|
| Servizio/action parametrizzata | 1 + 2 |
| Refactor Poi Type | 3 |
| Main solo tra selezionati | 2 (`fields` + service validation) |
| Conflitto pivot keep Main | 1 |
| Pivot theme/activity/poi_type | 1 + 3 |
| Escludere Main dalla delete | 1 |
| Transazione + messaggi | 1 (transaction) + 2 (Nova messages) |
| Conferma irreversibile | 2 (`confirmText` + Heading) |
| Test automatici | 1 |
| Smoke + note operative | 4 |
| Out of scope Where/When/Target, reindex, metadata | rispettato (nessun task) |
