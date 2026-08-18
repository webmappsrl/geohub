> Ticket: oc:8361

# Fix aggiornamento featured image POI da OSM/Wikimedia Commons Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Correggere `geohub:update_pois_from_osm` (e l'importer iniziale POI OSM) affinché la featured image dei POI con tag `wikimedia_commons` venga effettivamente aggiornata quando il file cambia su Wikimedia Commons, con errori visibili invece di fallimenti silenziosi, thumbnails rigenerate, e un `--dry-run` per verificare l'impatto prima del rollout su ~1272 media esistenti.

**Architecture:** Il blocco di sincronizzazione dell'immagine Wikimedia viene estratto dal monolitico `updatePoiData()` in un metodo dedicato `updateFeatureImageFromWikimedia()`, con un criterio di confronto isolato in `shouldUpdateFeatureImage()` (filename normalizzato con fallback sulla data) e una query di geometria sicura isolata in `getPoiGeometryWkt()`. Nessuna migration: tutto il criterio si basa su dati già disponibili a runtime (URL salvato + risposta Wikimedia).

**Tech Stack:** Laravel (PHP), PHPUnit (stile Feature test già presente nel repo, classi non-Pest), `Illuminate\Support\Facades\Http` per le chiamate a Wikimedia Commons, `Http::fake()` per isolare i test dalla rete reale verso Wikimedia (introdotto per la prima volta in questo repo — le chiamate OSM esistenti restano reali, non mockate, invariato rispetto ai test già presenti).

**Spec:** `docs/features/8361-fix-aggiornamento-featured-image-osm-wikimedia/overview.md`

## Global Constraints

- Nessuna migration o nuova colonna sul database — il criterio di confronto usa solo dati già disponibili a runtime (vincolo esplicito del dev)
- Nessun commit/push/branch automatico durante l'esecuzione — i comandi `git commit` sono istruzioni testuali per l'utente, eseguite solo con conferma esplicita
- Branch già creato e attivo: `feature/oc-8361-fix-aggiornamento-featured-image-osm-wikimedia` — non ricrearlo
- Commit convention: `fix(oc:8361): ...` (o `feat(oc:8361): ...` per il flag `--dry-run`, nuova funzionalità operativa)
- Nessun testo user-facing introdotto (fix backend, nessuna i18n richiesta)
- Segui lo stile dei test Feature già presenti in `tests/Feature/UpdatePOIFromOsmTest.php` (classe PHPUnit, non Pest)
- Applica le convenzioni della skill `wm-skills:our-code-style` (già rispettato nel codice qui sotto: metodi privati piccoli e focalizzati, niente commenti superflui)

---

### Task 1: Fix download Wikimedia (User-Agent, validazione HTTP), geometria sicura, preservazione `description`

**Files:**
- Modify: `config/geohub.php`
- Modify: `app/Console/Commands/UpdatePOIFromOsm.php:130-270` (estrae il blocco di sync immagine Wikimedia in un metodo dedicato)
- Test: `tests/Feature/UpdatePOIFromOsmTest.php`

**Interfaces:**
- Produces: `config('geohub.wikimedia_user_agent')` — stringa User-Agent condivisa, consumata anche da Task 6 in `OutSourceImporterFeatureOSMPoi`
- Produces: `UpdatePOIFromOsm::getPoiGeometryWkt(EcPoi $poi): string` — query sicura parametrizzata, consumata internamente da `updateFeatureImageFromWikimedia()`
- Produces: `UpdatePOIFromOsm::updateFeatureImageFromWikimedia(EcPoi $poi, array $osmPoi): void` — sostituisce il vecchio blocco inline in `updatePoiData()`; Task 2 modificherà il suo criterio "is up to date", Task 3 aggiungerà il dispatch della chain di enrichment, Task 5 aggiungerà il ramo `--dry-run`

- [x] **Step 1: Aggiungi la configurazione dello User-Agent**

Modifica `config/geohub.php`, aggiungendo questa riga dopo `'node_executable'`:

```php
    'node_executable' => env('NODE_EXECUTABLE', '/usr/bin/node'),
    'wikimedia_user_agent' => env('WIKIMEDIA_USER_AGENT', 'GeoHub-POI-Updater/1.0 (https://geohub.webmapp.it; info@webmapp.it)'),
```

- [x] **Step 2: Scrivi i test che falliscono**

Aggiungi in cima a `tests/Feature/UpdatePOIFromOsmTest.php` gli use statement mancanti:

```php
use App\Models\EcMedia;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
```

Nota: questo repo usa Laravel 8.83, che espone solo `expectsOutput()` (match esatto) e `doesntExpectOutput()` sull'helper `$this->artisan()` — non esistono `expectsOutputToContain()`/`doesntExpectOutputToContain()` (introdotti in Laravel 9.21+). Per i test che devono verificare un **frammento** di output (non l'intera riga esatta, spesso perché contiene un nome POI generato da Faker), usa `Artisan::call(...)` seguito da un'asserzione su `Artisan::output()` con `assertStringContainsString()`/`assertStringNotContainsString()`, come mostrato nei test seguenti.

Aggiungi un `setUp()` alla classe `UpdatePOIFromOsmTest` (non esiste ancora):

```php
    protected function setUp(): void
    {
        parent::setUp();

        // config('geohub.ec_media_storage_name') resolves to a real S3 disk in this repo's
        // .env (no .env.testing override) — fake it once here so no test in this class,
        // new or pre-existing, ever writes to real S3.
        Storage::fake(config('geohub.ec_media_storage_name'));
    }
```

Aggiungi questo metodo helper privato alla classe `UpdatePOIFromOsmTest` (usato da tutti i test di questo piano):

```php
    /**
     * Build a fake OSM geojson response (as returned by OsmClient::getGeojson)
     * for a node with the given osmid and wikimedia_commons tag.
     */
    private function fakeOsmGeojsonWithWikimediaCommons(string $osmid, string $wikimediaCommonsTitle, array $extraProperties = []): string
    {
        return json_encode([
            'version' => 0.6,
            'generator' => 'test',
            '_osmid' => $osmid,
            'type' => 'Feature',
            '_api_url' => 'https://api.openstreetmap.org/api/0.6/node/'.$osmid.'.json',
            'properties' => array_merge([
                'wikimedia_commons' => $wikimediaCommonsTitle,
                'name' => 'Test POI',
            ], $extraProperties),
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [10.43, 43.70],
            ],
        ]);
    }

    /**
     * Fake the Wikimedia Commons metadata + image download endpoints.
     */
    private function fakeWikimediaResponses(string $title, string $timestamp, int $imageStatus = 200): void
    {
        Http::fake([
            'commons.wikimedia.org/*' => Http::response([
                'query' => [
                    'pages' => [
                        '12345' => [
                            'title' => $title,
                            'imageinfo' => [
                                [
                                    'timestamp' => $timestamp,
                                    'url' => 'https://upload.wikimedia.org/wikipedia/commons/t/te/'.$title,
                                    'sha1' => 'abc123',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'upload.wikimedia.org/*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/EcMedia/test_resize.jpg')),
                $imageStatus
            ),
        ]);
    }
```

Aggiungi questi nuovi metodi di test alla classe:

```php
    /**
     * Test that the Wikimedia image download uses the configured User-Agent header.
     *
     * @return void
     */
    public function test_wikimedia_image_download_uses_configured_user_agent()
    {
        $user = User::factory()->create();
        $oldMedia = EcMedia::factory()->create();
        DB::table('ec_media')->where('id', $oldMedia->id)->update([
            'url' => 'https://old-storage.test/ec_media/File:OldPhoto.jpg',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
        $oldMedia->refresh();

        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '111111111',
            'feature_image' => $oldMedia->id,
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn($this->fakeOsmGeojsonWithWikimediaCommons('111111111', 'File:Test-image.jpg'));

        $this->fakeWikimediaResponses('File:Test-image.jpg', '2024-06-01T10:00:00Z');

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'upload.wikimedia.org')
                && $request->hasHeader('User-Agent', config('geohub.wikimedia_user_agent'));
        });
    }

    /**
     * Test that a failed Wikimedia image download is reported visibly and does not crash the command.
     *
     * @return void
     */
    public function test_wikimedia_image_download_failure_is_visible()
    {
        $user = User::factory()->create();
        $oldMedia = EcMedia::factory()->create();
        DB::table('ec_media')->where('id', $oldMedia->id)->update([
            'url' => 'https://old-storage.test/ec_media/File:OldPhoto.jpg',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
        $oldMedia->refresh();

        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '222222222',
            'feature_image' => $oldMedia->id,
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn($this->fakeOsmGeojsonWithWikimediaCommons('222222222', 'File:Test-image.jpg'));

        $this->fakeWikimediaResponses('File:Test-image.jpg', '2024-06-01T10:00:00Z', 403);

        Artisan::call('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
        ]);

        $this->assertStringContainsString('Error downloading image from Wikimedia Commons', Artisan::output());

        $this->assertDatabaseHas('ec_media', [
            'id' => $oldMedia->id,
            'url' => 'https://old-storage.test/ec_media/File:OldPhoto.jpg',
        ]);
    }

    /**
     * Test that the media geometry is built via a safe parameterized query, not string interpolation.
     *
     * @return void
     */
    public function test_media_geometry_is_updated_via_safe_query()
    {
        $user = User::factory()->create();
        $oldMedia = EcMedia::factory()->create();
        DB::table('ec_media')->where('id', $oldMedia->id)->update([
            'url' => 'https://old-storage.test/ec_media/File:OldPhoto.jpg',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
        $oldMedia->refresh();

        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '333333333',
            'feature_image' => $oldMedia->id,
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11.25 44.50)'))"),
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn($this->fakeOsmGeojsonWithWikimediaCommons('333333333', 'File:Test-image.jpg'));

        $this->fakeWikimediaResponses('File:Test-image.jpg', '2024-06-01T10:00:00Z');

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
        ]);

        $wkt = DB::select('SELECT ST_AsText(geometry) AS wkt FROM ec_media WHERE id = ?', [$oldMedia->id])[0]->wkt;
        $this->assertStringContainsString('11.25 44.5', $wkt);
    }

    /**
     * Test that an existing manual description is preserved when the featured image is updated.
     *
     * @return void
     */
    public function test_existing_description_is_preserved_on_update()
    {
        $user = User::factory()->create();
        $oldMedia = EcMedia::factory()->create([
            'description' => ['it' => 'Descrizione manuale importante', 'en' => 'Important manual description'],
        ]);
        DB::table('ec_media')->where('id', $oldMedia->id)->update([
            'url' => 'https://old-storage.test/ec_media/File:OldPhoto.jpg',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
        $oldMedia->refresh();

        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '444444444',
            'feature_image' => $oldMedia->id,
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn($this->fakeOsmGeojsonWithWikimediaCommons('444444444', 'File:Test-image.jpg'));

        $this->fakeWikimediaResponses('File:Test-image.jpg', '2024-06-01T10:00:00Z');

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
        ]);

        $oldMedia->refresh();
        $this->assertEquals('Descrizione manuale importante', $oldMedia->getTranslation('description', 'it'));
    }

    /**
     * Regression: a poi without wikimedia_commons tag is not touched by the media sync block.
     *
     * @return void
     */
    public function test_poi_without_wikimedia_commons_tag_is_not_touched()
    {
        $user = User::factory()->create();
        $oldMedia = EcMedia::factory()->create();
        DB::table('ec_media')->where('id', $oldMedia->id)->update([
            'url' => 'https://old-storage.test/ec_media/File:OldPhoto.jpg',
        ]);
        $oldMedia->refresh();

        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '555555555',
            'feature_image' => $oldMedia->id,
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn(json_encode([
                'version' => 0.6,
                'generator' => 'test',
                '_osmid' => '555555555',
                'type' => 'Feature',
                '_api_url' => 'https://api.openstreetmap.org/api/0.6/node/555555555.json',
                'properties' => ['name' => 'No image POI'],
                'geometry' => ['type' => 'Point', 'coordinates' => [10.43, 43.70]],
            ]));

        Http::fake();

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
        ]);

        // Not assertNothingSent(): saving the poi (regardless of the wikimedia_commons
        // check) also triggers EcPoi::updateDataChain() -> UpdateEcPoiDemJob, which makes
        // its own unrelated Http::get() call to the DEM elevation service — pre-existing
        // behavior, nothing to do with this ticket. Scope the assertion to Wikimedia only.
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'wikimedia.org');
        });
        $this->assertDatabaseHas('ec_media', [
            'id' => $oldMedia->id,
            'url' => 'https://old-storage.test/ec_media/File:OldPhoto.jpg',
        ]);
    }
```

- [x] **Step 3: Esegui i test per verificare che falliscano**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: FAIL — `test_wikimedia_image_download_uses_configured_user_agent`, `test_wikimedia_image_download_failure_is_visible`, `test_media_geometry_is_updated_via_safe_query`, `test_existing_description_is_preserved_on_update` falliscono (il codice attuale usa `file_get_contents` senza header custom verificabile via `Http::fake`, non valida la risposta, interpola la geometria in modo diverso, e azzera sempre `description`). `test_poi_without_wikimedia_commons_tag_is_not_touched` dovrebbe già passare (comportamento invariato) — se fallisce, verificare l'helper prima di continuare.

- [x] **Step 4: Implementa il fix minimo**

Sostituisci in `app/Console/Commands/UpdatePOIFromOsm.php` il blocco che va da `if (array_key_exists('properties', $osmPoi) && array_key_exists('wikimedia_commons', $osmPoi['properties'])) {` (riga 152) fino alla chiusura di quel blocco `if` (riga 252, la riga con il solo `}` prima di `// Update the 'ele' attribute...`), con questa singola riga:

```php
        if (array_key_exists('properties', $osmPoi) && array_key_exists('wikimedia_commons', $osmPoi['properties'])) {
            $this->updateFeatureImageFromWikimedia($poi, $osmPoi);
        }
```

Poi aggiungi questi due nuovi metodi privati alla classe `UpdatePOIFromOsm`, subito dopo la chiusura di `updatePoiData()`:

```php
    private function updateFeatureImageFromWikimedia(EcPoi $poi, array $osmPoi): void
    {
        $wikimediaCommonsTitle = $osmPoi['properties']['wikimedia_commons'];
        $metadataUrl = 'https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&iiprop=timestamp|url|sha1&format=json&titles='.$wikimediaCommonsTitle;

        try {
            $this->info('Making HTTP request to: '.$metadataUrl);
            $metadataResponse = Http::withHeaders([
                'User-Agent' => config('geohub.wikimedia_user_agent'),
            ])->get($metadataUrl);

            $responseData = json_decode($metadataResponse->body(), true);

            if ($responseData === null) {
                $this->error('Invalid JSON response from Wikimedia Commons for poi '.$poi->name);
                array_push($this->errorPois, $poi);

                return;
            }

            if (! isset($responseData['query']['pages'])) {
                $this->error('No pages found in Wikimedia Commons response for poi '.$poi->name);
                array_push($this->errorPois, $poi);

                return;
            }

            $pages = $responseData['query']['pages'];
        } catch (Exception $e) {
            $this->error('Error while retrieving metadata from Wikimedia Commons for poi '.$poi->name.' ('.$wikimediaCommonsTitle.'). Error: '.$e->getMessage());
            array_push($this->errorPois, $poi);

            return;
        }

        if (empty($pages)) {
            $this->error('No pages data available for poi '.$poi->name);
            array_push($this->errorPois, $poi);

            return;
        }

        foreach ($pages as $pageId => $page) {
            if (! isset($page['imageinfo'][0])) {
                $this->error('No imageinfo available for page in poi '.$poi->name);

                continue;
            }

            $imageUrl = $page['imageinfo'][0]['url'];
            $imageUpdatedAt = new \DateTime($page['imageinfo'][0]['timestamp']);
            $currentFeatureImage = $poi->featureImage;

            if ($currentFeatureImage && new \DateTime($currentFeatureImage->updated_at) >= $imageUpdatedAt && ! empty($currentFeatureImage->url)) {
                $this->info('[is up to date] Feature image for poi '.$poi->name.'.');

                continue;
            }

            $this->info('[updating] Feature image for poi '.$poi->name);

            try {
                $imageResponse = Http::withHeaders([
                    'User-Agent' => config('geohub.wikimedia_user_agent'),
                ])->get($imageUrl);

                if (! $imageResponse->successful() || empty($imageResponse->body())) {
                    $this->error('Error downloading image from Wikimedia Commons for poi '.$poi->name.' ('.$imageUrl.'). HTTP status: '.$imageResponse->status());
                    array_push($this->errorPois, $poi);

                    continue;
                }

                $ec_storage_name = config('geohub.ec_media_storage_name');
                $media_path = 'ec_media/'.$page['title'];
                Storage::disk($ec_storage_name)->put($media_path, $imageResponse->body());
                Log::info('Updating EC Media.');

                if ($currentFeatureImage) {
                    $currentFeatureImage->geometry = $this->getPoiGeometryWkt($poi);
                    if (empty($currentFeatureImage->description)) {
                        $currentFeatureImage->description = '';
                    }
                    $currentFeatureImage->url = Storage::disk($ec_storage_name)->url($media_path);
                    $currentFeatureImage->save();
                } else {
                    $ec_media = EcMedia::create([
                        'user_id' => 1,
                        'name' => $poi->name,
                        'geometry' => $this->getPoiGeometryWkt($poi),
                        'url' => '',
                        'description' => '',
                    ]);
                    $ec_media->url = Storage::disk($ec_storage_name)->url($media_path);
                    $ec_media->save();
                    $poi->featureImage()->associate($ec_media);
                }

                if ($poi->ecMedia()->count() < 1 && $poi->feature_image) {
                    Log::info('Updating: '.$poi->id);
                    $poi->ecMedia()->sync($poi->featureImage);
                }
            } catch (Exception $e) {
                $this->error('Error updating EcMedia for poi '.$poi->name.' (id: '.$poi->id.'): '.$e->getMessage());
                Log::info('Error updating EcMedia with POI id: '.$poi->id."\n ERROR: ".$e->getMessage());
                array_push($this->errorPois, $poi);
            }
        }
    }

    private function getPoiGeometryWkt(EcPoi $poi): string
    {
        return DB::select('SELECT ST_AsText(geometry) AS wkt FROM ec_pois WHERE id = ?', [$poi->id])[0]->wkt;
    }
```

Nota: `$poi->featureImage()->associate($ec_media)` associa la relazione in memoria ma non salva il POI — questo comportamento è invariato rispetto al codice originale (il salvataggio avviene più avanti in `updatePoiData()` con `$poi->save()`).

- [x] **Step 5: Esegui i test per verificare che passino**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: PASS su tutti i test nuovi e sui 5 test preesistenti (questi ultimi fanno rete reale verso OSM, comportamento invariato).

- [x] **Step 6: Commit**

```bash
git add config/geohub.php app/Console/Commands/UpdatePOIFromOsm.php tests/Feature/UpdatePOIFromOsmTest.php
git commit -m "fix(oc:8361): use proper User-Agent and safe geometry query for Wikimedia image sync"
```

---

### Task 2: Criterio combinato filename normalizzato + fallback data

**Files:**
- Modify: `app/Console/Commands/UpdatePOIFromOsm.php` (metodo `updateFeatureImageFromWikimedia`, aggiunto in Task 1)
- Test: `tests/Feature/UpdatePOIFromOsmTest.php`

**Interfaces:**
- Consumes: `updateFeatureImageFromWikimedia()` da Task 1
- Produces: `UpdatePOIFromOsm::shouldUpdateFeatureImage(EcMedia $currentFeatureImage, array $page): bool` — usato internamente, sostituisce il confronto inline sulle date

- [x] **Step 1: Scrivi i test che falliscono**

Aggiungi questi metodi di test:

```php
    /**
     * Test that a filename change forces the update even if the local record has a newer updated_at.
     *
     * @return void
     */
    public function test_force_update_on_filename_change_even_with_newer_local_date()
    {
        $user = User::factory()->create();
        $oldMedia = EcMedia::factory()->create();
        // local record is "newer" than the Wikimedia timestamp we'll fake below
        DB::table('ec_media')->where('id', $oldMedia->id)->update([
            'url' => 'https://old-storage.test/ec_media/File:It-pr-ldpB072.jpg',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        $oldMedia->refresh();

        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '666666666',
            'feature_image' => $oldMedia->id,
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn($this->fakeOsmGeojsonWithWikimediaCommons('666666666', 'File:It-pr-ldpB072v2.jpg'));

        // Wikimedia timestamp is OLDER than the local updated_at, but the filename changed
        $this->fakeWikimediaResponses('File:It-pr-ldpB072v2.jpg', '2024-06-01T10:00:00Z');

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
        ]);

        $oldMedia->refresh();
        $this->assertStringContainsString('It-pr-ldpB072v2.jpg', $oldMedia->url);
    }

    /**
     * Test that the image is skipped when the filename is unchanged and the date is not newer.
     *
     * @return void
     */
    public function test_skip_update_when_filename_unchanged_and_date_not_newer()
    {
        $user = User::factory()->create();
        $oldMedia = EcMedia::factory()->create();
        DB::table('ec_media')->where('id', $oldMedia->id)->update([
            'url' => 'https://old-storage.test/ec_media/File:Same-name.jpg',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        $oldMedia->refresh();

        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '777777777',
            'feature_image' => $oldMedia->id,
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn($this->fakeOsmGeojsonWithWikimediaCommons('777777777', 'File:Same-name.jpg'));

        $this->fakeWikimediaResponses('File:Same-name.jpg', '2024-06-01T10:00:00Z');

        Artisan::call('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
        ]);

        $this->assertStringContainsString('[is up to date]', Artisan::output());

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'upload.wikimedia.org');
        });
    }

    /**
     * Test that a non-ASCII Commons filename converges after a single run (no perpetual mismatch loop).
     *
     * @return void
     */
    public function test_filename_comparison_converges_for_non_ascii_titles()
    {
        $user = User::factory()->create();
        $title = 'File:Rifugio-così-è.jpg';
        $storedUrl = 'https://storage.test/ec_media/'.rawurlencode($title);

        $oldMedia = EcMedia::factory()->create();
        DB::table('ec_media')->where('id', $oldMedia->id)->update([
            'url' => $storedUrl,
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        $oldMedia->refresh();

        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '888888888',
            'feature_image' => $oldMedia->id,
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn($this->fakeOsmGeojsonWithWikimediaCommons('888888888', $title));

        // same title, older Wikimedia timestamp than local: with correctly normalized
        // comparison this must be recognized as "already up to date", not forced again.
        $this->fakeWikimediaResponses($title, '2024-06-01T10:00:00Z');

        Artisan::call('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
        ]);

        $this->assertStringContainsString('[is up to date]', Artisan::output());
    }
```

- [x] **Step 2: Esegui i test per verificare che falliscano**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: FAIL su `test_force_update_on_filename_change_even_with_newer_local_date` (il criterio attuale confronta solo le date, quindi salterebbe l'update) e potenzialmente su `test_filename_comparison_converges_for_non_ascii_titles` se l'encoding non è ancora normalizzato.

- [x] **Step 3: Implementa il criterio combinato**

In `app/Console/Commands/UpdatePOIFromOsm.php`, dentro `updateFeatureImageFromWikimedia()`, sostituisci:

```php
            $imageUrl = $page['imageinfo'][0]['url'];
            $imageUpdatedAt = new \DateTime($page['imageinfo'][0]['timestamp']);
            $currentFeatureImage = $poi->featureImage;

            if ($currentFeatureImage && new \DateTime($currentFeatureImage->updated_at) >= $imageUpdatedAt && ! empty($currentFeatureImage->url)) {
                $this->info('[is up to date] Feature image for poi '.$poi->name.'.');

                continue;
            }
```

con:

```php
            $imageUrl = $page['imageinfo'][0]['url'];
            $currentFeatureImage = $poi->featureImage;

            if ($currentFeatureImage && ! $this->shouldUpdateFeatureImage($currentFeatureImage, $page)) {
                $this->info('[is up to date] Feature image for poi '.$poi->name.'.');

                continue;
            }
```

Poi aggiungi questo nuovo metodo privato, subito dopo `updateFeatureImageFromWikimedia()`:

```php
    private function shouldUpdateFeatureImage(EcMedia $currentFeatureImage, array $page): bool
    {
        if (empty($currentFeatureImage->url)) {
            return true;
        }

        $currentFilename = rawurldecode(basename($currentFeatureImage->url));
        $newFilename = rawurldecode($page['title']);

        if ($currentFilename !== $newFilename) {
            return true;
        }

        $imageUpdatedAt = new \DateTime($page['imageinfo'][0]['timestamp']);

        return new \DateTime($currentFeatureImage->updated_at) < $imageUpdatedAt;
    }
```

- [x] **Step 4: Esegui i test per verificare che passino**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: PASS su tutti i test, inclusi quelli di Task 1.

- [x] **Step 5: Commit**

```bash
git add app/Console/Commands/UpdatePOIFromOsm.php tests/Feature/UpdatePOIFromOsmTest.php
git commit -m "fix(oc:8361): force featured image update on Commons filename change, not just date"
```

---

### Task 3: Rigenerazione thumbnails sull'update di un media esistente (dispatch `updateDataChain`) + conferma comportamento EXIF

**Files:**
- Modify: `app/Console/Commands/UpdatePOIFromOsm.php` (metodo `updateFeatureImageFromWikimedia`)
- Test: `tests/Feature/UpdatePOIFromOsmTest.php`

**Interfaces:**
- Consumes: `EcMedia::updateDataChain(EcMedia $model): void` — già esistente in `app/Models/EcMedia.php:221`, oggi chiamato solo da `static::created()`

- [x] **Step 1: Scrivi i test che falliscono**

Aggiungi in cima al file l'ulteriore use statement:

```php
use Illuminate\Support\Facades\Bus;
```

Aggiungi questi metodi di test:

```php
    /**
     * Test that updating an EXISTING featured image re-dispatches the enrichment chain
     * (today it only fires on EcMedia::created), so thumbnails get regenerated.
     *
     * @return void
     */
    public function test_enrichment_chain_is_dispatched_when_updating_existing_media()
    {
        $user = User::factory()->create();
        $oldMedia = EcMedia::factory()->create();
        DB::table('ec_media')->where('id', $oldMedia->id)->update([
            'url' => 'https://old-storage.test/ec_media/File:OldPhoto.jpg',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
        $oldMedia->refresh();

        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '999999999',
            'feature_image' => $oldMedia->id,
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn($this->fakeOsmGeojsonWithWikimediaCommons('999999999', 'File:Test-image.jpg'));

        $this->fakeWikimediaResponses('File:Test-image.jpg', '2024-06-01T10:00:00Z');

        // Bus::fake() is set up only now, right before the act phase: EcMedia::factory()->create()
        // above already fired its own real (unfaked) enrichment chain via the static::created hook
        // (harmless, uses the factory's default local fixture) — faking earlier would let that
        // unrelated dispatch satisfy the assertChained() below even without this task's fix.
        Bus::fake();

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
        ]);

        Bus::assertChained([
            \App\Jobs\UpdateEcMedia::class,
            \App\Jobs\UpdateModelWithGeometryTaxonomyWhere::class,
        ]);
    }

    /**
     * Confirm existing behavior (job-level, not through the command): when the media's
     * image has NO GPS EXIF, the enrichment job does not touch its geometry. Uses the
     * same local fixture convention already established by EcMediaFactory (url pointing
     * to a real file under the 'public' disk) to avoid depending on the real S3 disk
     * (config('geohub.ec_media_storage_name')) that this repo's .env points to.
     *
     * @return void
     */
    public function test_enrichment_job_does_not_touch_geometry_when_image_has_no_exif_gps()
    {
        // test_108x137.jpg is one of the fixtures EcMediaFactory itself already puts on
        // the 'public' disk unconditionally, and has NO GPS EXIF (verified locally).
        $media = EcMedia::factory()->create([
            'url' => '/ec_media_test/test_108x137.jpg',
            'geometry' => DB::raw('ST_MakePoint(11.25, 44.50)'),
        ]);

        $wkt = DB::select('SELECT ST_AsText(geometry) AS wkt FROM ec_media WHERE id = ?', [$media->id])[0]->wkt;
        $this->assertStringContainsString('11.25 44.5', $wkt);
    }

    /**
     * Confirm existing behavior (job-level, not through the command): when the media's
     * image HAS GPS EXIF, the enrichment job overwrites its geometry with the EXIF
     * coordinates. Not a regression introduced by this ticket: this is the pre-existing
     * UpdateEcMedia job behavior (already exercised today on every EcMedia::create()),
     * now also reachable when this ticket's fix re-dispatches it on update.
     *
     * @return void
     */
    public function test_enrichment_job_overwrites_geometry_when_image_has_gps()
    {
        // EcMediaFactory's own default url ('/ec_media_test/test.jpg') already points to
        // the fixture confirmed to HAVE GPS EXIF: lat ~43.781289, lon ~10.448261 (verified locally).
        $media = EcMedia::factory()->create([
            'geometry' => DB::raw('ST_MakePoint(11.25, 44.50)'),
        ]);

        $wkt = DB::select('SELECT ST_AsText(geometry) AS wkt FROM ec_media WHERE id = ?', [$media->id])[0]->wkt;
        $this->assertStringContainsString('10.448', $wkt);
        $this->assertStringContainsString('43.781', $wkt);
    }
```

- [x] **Step 2: Esegui i test per verificare che falliscano**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: FAIL solo su `test_enrichment_chain_is_dispatched_when_updating_existing_media` (oggi la chain non viene dispacciata sull'update di un media esistente). `test_enrichment_job_does_not_touch_geometry_when_image_has_no_exif_gps` e `test_enrichment_job_overwrites_geometry_when_image_has_gps` esercitano il job `UpdateEcMedia` tramite l'hook `EcMedia::created` già esistente (non tramite il comando), quindi **dovrebbero già passare** anche prima di questo task — sono test di conferma/regressione sul comportamento preesistente del job, non guidano un'implementazione nuova. Eseguili comunque ora per avere una baseline verde prima di procedere.

- [x] **Step 3: Implementa il dispatch della chain sul ramo di update**

In `app/Console/Commands/UpdatePOIFromOsm.php`, dentro `updateFeatureImageFromWikimedia()`, nel ramo `if ($currentFeatureImage) { ... }`, aggiungi la chiamata dopo `$currentFeatureImage->save();`:

```php
                if ($currentFeatureImage) {
                    $currentFeatureImage->geometry = $this->getPoiGeometryWkt($poi);
                    if (empty($currentFeatureImage->description)) {
                        $currentFeatureImage->description = '';
                    }
                    $currentFeatureImage->url = Storage::disk($ec_storage_name)->url($media_path);
                    $currentFeatureImage->save();
                    $currentFeatureImage->updateDataChain($currentFeatureImage);
                } else {
```

- [x] **Step 4: Esegui i test per verificare che passino**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: PASS su tutti i test.

- [x] **Step 5: Commit**

```bash
git add app/Console/Commands/UpdatePOIFromOsm.php tests/Feature/UpdatePOIFromOsmTest.php
git commit -m "fix(oc:8361): re-dispatch EcMedia enrichment chain when an existing featured image is updated"
```

---

### Task 4: Blocco attributi POI resiliente a `properties` OSM assente/null

**Files:**
- Modify: `app/Console/Commands/UpdatePOIFromOsm.php` (metodo `updatePoiData`)
- Test: `tests/Feature/UpdatePOIFromOsmTest.php`

**Interfaces:**
- Nessuna nuova interfaccia pubblica: modifica solo il flow di controllo interno di `updatePoiData()`

- [x] **Step 1: Scrivi il test che fallisce**

Aggiungi questo metodo di test:

```php
    /**
     * Test that a poi with null OSM properties does not crash the whole batch:
     * the error is reported and the poi is skipped, other pois keep being processed.
     *
     * @return void
     */
    public function test_poi_with_null_osm_properties_does_not_interrupt_the_batch()
    {
        $user = User::factory()->create();

        $brokenPoi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '131313131',
        ]);
        $healthyPoi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '141414141',
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->with('node/131313131')
            ->once()
            ->andReturn(json_encode([
                'version' => 0.6,
                'generator' => 'test',
                '_osmid' => '131313131',
                'type' => 'Feature',
                '_api_url' => 'https://api.openstreetmap.org/api/0.6/node/131313131.json',
                'properties' => null,
                'geometry' => ['type' => 'Point', 'coordinates' => [10.43, 43.70]],
            ]));

        OsmClient::shouldReceive('getGeojson')
            ->with('node/141414141')
            ->once()
            ->andReturn(json_encode([
                'version' => 0.6,
                'generator' => 'test',
                '_osmid' => '141414141',
                'type' => 'Feature',
                '_api_url' => 'https://api.openstreetmap.org/api/0.6/node/141414141.json',
                'properties' => ['name' => 'Healthy POI', 'ele' => '123'],
                'geometry' => ['type' => 'Point', 'coordinates' => [10.43, 43.70]],
            ]));

        Artisan::call('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Error updating attributes for poi', $output);
        $this->assertStringContainsString('Finished.', $output);

        $this->assertDatabaseHas('ec_pois', [
            'id' => $healthyPoi->id,
            'ele' => 123,
        ]);
    }
```

- [x] **Step 2: Esegui il test per verificare che fallisca**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: FAIL — con `properties` null, `array_key_exists('ref', $osmPoi['properties'])` in `updatePoiName()`/`updatePoiAttribute()` solleva un `TypeError` (non un `Exception`), non catturato da nessun try/catch esistente: il test PHPUnit fallisce con un errore fatale non gestito, e `$healthyPoi` non viene mai processato perché il loop si interrompe.

- [x] **Step 3: Implementa la protezione**

In `app/Console/Commands/UpdatePOIFromOsm.php`, aggiungi l'import in cima al file:

```php
use Throwable;
```

Poi, dentro `updatePoiData()`, sostituisci:

```php
        // Update the 'ele' attribute of the poi if it exists in the OSM data
        $this->updatePoiAttribute($poi, $osmPoi, 'ele', 'ele');
        // Update the 'ref' attribute of the poi if it exists in the OSM data
        $this->updatePoiAttribute($poi, $osmPoi, 'ref', 'ref');
        // Update the name of the poi if the 'name' key exists in the OSM data
        $this->updatePoiName($poi, $osmPoi);
        $this->updatePoiGeometry($poi, $osmPoi);

        // Set the 'skip_geomixer_tech' field to true if the 'ele' attribute was updated
        if ($poi->isDirty('ele')) {
```

con:

```php
        try {
            if (! isset($osmPoi['properties']) || ! is_array($osmPoi['properties'])) {
                throw new Exception('Missing or invalid "properties" in OSM data for poi '.$poi->name);
            }
            $this->updatePoiAttribute($poi, $osmPoi, 'ele', 'ele');
            $this->updatePoiAttribute($poi, $osmPoi, 'ref', 'ref');
            $this->updatePoiName($poi, $osmPoi);
            $this->updatePoiGeometry($poi, $osmPoi);
        } catch (Throwable $e) {
            $this->error('Error updating attributes for poi '.$poi->name.' ('.$poi->osmid.'). Error: '.$e->getMessage());
            array_push($this->errorPois, $poi);

            return;
        }

        // Set the 'skip_geomixer_tech' field to true if the 'ele' attribute was updated
        if ($poi->isDirty('ele')) {
```

Nota tecnica (perché `catch (Throwable $e)` e non `catch (Exception $e)`): `array_key_exists($key, null)` solleva un `TypeError`, che in PHP implementa `\Throwable` ma NON estende `\Exception` — un catch su `Exception` non lo intercetterebbe. Il guard esplicito su `is_array($osmPoi['properties'])` previene già il caso concreto testato; il catch su `Throwable` resta come rete di sicurezza per qualunque altro errore imprevisto nello stesso blocco.

- [x] **Step 4: Esegui il test per verificare che passi**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: PASS su tutti i test.

- [x] **Step 5: Commit**

```bash
git add app/Console/Commands/UpdatePOIFromOsm.php tests/Feature/UpdatePOIFromOsmTest.php
git commit -m "fix(oc:8361): do not let a poi with invalid OSM properties crash the whole sync batch"
```

---

### Task 5: Flag `--dry-run`

**Files:**
- Modify: `app/Console/Commands/UpdatePOIFromOsm.php`
- Test: `tests/Feature/UpdatePOIFromOsmTest.php`

**Interfaces:**
- Produces: opzione CLI `--dry-run` sul comando `geohub:update_pois_from_osm`
- Consumes: `updateFeatureImageFromWikimedia()`, `shouldUpdateFeatureImage()`, `generatePoisJson()` (tutti da task precedenti)

- [x] **Step 1: Scrivi i test che falliscono**

Aggiungi questi metodi di test:

```php
    /**
     * Test that --dry-run reports the planned update without downloading or saving anything.
     *
     * @return void
     */
    public function test_dry_run_reports_planned_update_without_side_effects()
    {
        $user = User::factory()->create();
        $oldMedia = EcMedia::factory()->create();
        DB::table('ec_media')->where('id', $oldMedia->id)->update([
            'url' => 'https://old-storage.test/ec_media/File:OldPhoto.jpg',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
        $oldMedia->refresh();

        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '151515151',
            'feature_image' => $oldMedia->id,
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn($this->fakeOsmGeojsonWithWikimediaCommons('151515151', 'File:NewPhoto.jpg'));

        $this->fakeWikimediaResponses('File:NewPhoto.jpg', '2024-06-01T10:00:00Z');

        Artisan::call('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
            '--dry-run' => true,
        ]);

        $this->assertStringContainsString('[dry-run]', Artisan::output());

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'upload.wikimedia.org');
        });
        $this->assertDatabaseHas('ec_media', [
            'id' => $oldMedia->id,
            'url' => 'https://old-storage.test/ec_media/File:OldPhoto.jpg',
        ]);
    }

    /**
     * Test that --dry-run does not trigger generatePoisJson (public JSON export).
     *
     * @return void
     */
    public function test_dry_run_does_not_generate_pois_json()
    {
        $user = User::factory()->create();
        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '161616161',
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn(json_encode([
                'version' => 0.6,
                'generator' => 'test',
                '_osmid' => '161616161',
                'type' => 'Feature',
                '_api_url' => 'https://api.openstreetmap.org/api/0.6/node/161616161.json',
                'properties' => ['name' => 'No image POI'],
                'geometry' => ['type' => 'Point', 'coordinates' => [10.43, 43.70]],
            ]));

        // Saving the poi's geometry fires EcPoiObserver::saved() -> EcPoi::updateDataChain(),
        // which dispatches UpdateEcPoiDemJob (a real Http::get() to the DEM elevation service)
        // synchronously under QUEUE_CONNECTION=sync — unrelated to dry-run, fake it so this
        // test stays hermetic (same pattern already used by test_poi_without_wikimedia_commons_tag_is_not_touched).
        Http::fake();

        Artisan::call('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--dry-run' => true,
        ]);

        $this->assertStringNotContainsString('Generating App POIs', Artisan::output());
    }
```

- [x] **Step 2: Esegui i test per verificare che falliscano**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: FAIL — l'opzione `--dry-run` non esiste ancora sul comando (Artisan risponde con errore "The '--dry-run' option does not exist").

- [x] **Step 3: Implementa il flag**

In `app/Console/Commands/UpdatePOIFromOsm.php`, modifica la signature:

```php
    protected $signature = 'geohub:update_pois_from_osm
                            {user_email : the mail of the user of which the POIs must be updated}
                            {--osmid=}
                            {--ec_poi_id= : the ID of the specific POI to update}
                            {--dry-run : simulate the featured image update without downloading or saving anything}';
```

Aggiungi la property dopo `protected $osmid;`:

```php
    protected $dryRun = false;
```

In `handle()`, subito dopo `$this->osmid = $this->option('osmid');`, aggiungi:

```php
        $this->dryRun = (bool) $this->option('dry-run');
```

Nello stesso metodo, sostituisci:

```php
        $this->generatePoisJson($user);

        $this->info('Finished.');
```

con:

```php
        if (! $this->dryRun) {
            $this->generatePoisJson($user);
        }

        $this->info('Finished.');
```

Dentro `updateFeatureImageFromWikimedia()`, nel loop `foreach ($pages as $pageId => $page) { ... }`, subito dopo il blocco `if ($currentFeatureImage && ! $this->shouldUpdateFeatureImage(...)) { ... continue; }`, aggiungi:

```php
            if ($this->dryRun) {
                $this->info('[dry-run] Feature image for poi '.$poi->name.' would be updated - current: '.($currentFeatureImage ? basename($currentFeatureImage->url) : '(none)').' -> new: '.$page['title']);

                continue;
            }

```

(subito prima di `$this->info('[updating] Feature image for poi '.$poi->name);`)

- [x] **Step 4: Esegui i test per verificare che passino**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: PASS su tutti i test.

- [x] **Step 5: Commit**

```bash
git add app/Console/Commands/UpdatePOIFromOsm.php tests/Feature/UpdatePOIFromOsmTest.php
git commit -m "feat(oc:8361): add --dry-run flag to preview featured image updates before rollout"
```

---

### Task 6: Allineamento User-Agent nell'importer iniziale POI OSM

**Files:**
- Modify: `app/Classes/OutSourceImporter/OutSourceImporterFeatureOSMPoi.php`
- Test: `tests/Feature/OutSourceImporter/OutSourceImporterFeatureOSMPoi/PrepareMediaTagsJsonTest.php` (nuovo file)

**Interfaces:**
- Consumes: `config('geohub.wikimedia_user_agent')` (da Task 1)

- [x] **Step 1: Scrivi il test che fallisce**

Il costruttore di `OutSourceImporterFeatureAbstract` (ereditato da `OutSourceImporterFeatureOSMPoi`) richiede 5 argomenti posizionali senza default: `string $type, string $endpoint, string $source_id, bool $only_related_url, Illuminate\Log\Logger $logChannel` — nessuno di questi esegue chiamate di rete o query DB, sono solo assegnazioni di proprietà, quindi è sicuro istanziare l'oggetto direttamente nel test.

Crea il file `tests/Feature/OutSourceImporter/OutSourceImporterFeatureOSMPoi/PrepareMediaTagsJsonTest.php`:

```php
<?php

use App\Classes\OutSourceImporter\OutSourceImporterFeatureOSMPoi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrepareMediaTagsJsonTest extends TestCase
{
    /**
     * Test that the initial OSM POI import downloads media with the configured User-Agent.
     *
     * @return void
     */
    public function test_media_download_uses_configured_user_agent()
    {
        // config('geohub.osf_media_storage_name') resolves to a real S3 disk in this repo's
        // .env (no .env.testing override) — fake it so the test never hits real S3.
        Storage::fake(config('geohub.osf_media_storage_name'));

        Http::fake([
            'upload.wikimedia.org/*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/EcMedia/test_resize.jpg')),
                200
            ),
        ]);

        $importer = new OutSourceImporterFeatureOSMPoi('poi', 'osmpoi:test', 'node/999', false, Log::channel('stack'));

        $media = [
            'query' => [
                'pages' => [
                    '1' => [
                        'title' => 'File:Test-image.jpg',
                        'imageinfo' => [
                            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/t/te/Test-image.jpg'],
                        ],
                    ],
                ],
            ],
        ];

        $importer->prepareMediaTagsJson($media);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'upload.wikimedia.org')
                && $request->hasHeader('User-Agent', config('geohub.wikimedia_user_agent'));
        });
    }
}
```

- [x] **Step 2: Esegui il test per verificare che fallisca**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=PrepareMediaTagsJsonTest`
Expected: FAIL — il metodo usa ancora `file_get_contents()` con `stream_context_create()`, non passa mai dalla facade `Http`, quindi `Http::assertSent()` non trova nessuna richiesta registrata.

- [x] **Step 3: Implementa il fix**

In `app/Classes/OutSourceImporter/OutSourceImporterFeatureOSMPoi.php`, aggiungi l'import in cima al file (dopo `use Illuminate\Support\Facades\DB;`):

```php
use Illuminate\Support\Facades\Http;
```

Sostituisci il corpo del `try` in `prepareMediaTagsJson()`:

```php
        try {
            // Saving the Media in to the s3-osfmedia storage (.env in production)
            $storage_name = config('geohub.osf_media_storage_name');
            $this->logChannel->info('Saving OSF MEDIA on storage '.$storage_name);
            $this->logChannel->info(' ');
            if (isset($media['imageinfo']) && isset($media['imageinfo'][0])) {
                $url_encoded = $media['imageinfo'][0]['url'];
            }
            $options = ['http' => ['user_agent' => 'custom user agent string']];
            $context = stream_context_create($options);
            $contents = file_get_contents($url_encoded, false, $context);
            $basename = explode('.', basename($url_encoded));
            $s3_osfmedia = Storage::disk($storage_name);
            $osf_name_tmp = sha1($basename[0]).'.'.$basename[1];
            $s3_osfmedia->put($osf_name_tmp, $contents);

            $this->logChannel->info('Saved OSF Media with name: '.$osf_name_tmp);
            $tags['url'] = ($s3_osfmedia->exists($osf_name_tmp)) ? $osf_name_tmp : '';
        } catch (Exception $e) {
            echo $e;
            $this->logChannel->error('Saving media in s3-osfmedia error:'.$e);
        }
```

con:

```php
        try {
            // Saving the Media in to the s3-osfmedia storage (.env in production)
            $storage_name = config('geohub.osf_media_storage_name');
            $this->logChannel->info('Saving OSF MEDIA on storage '.$storage_name);
            $this->logChannel->info(' ');
            if (isset($media['imageinfo']) && isset($media['imageinfo'][0])) {
                $url_encoded = $media['imageinfo'][0]['url'];
            }
            $imageResponse = Http::withHeaders([
                'User-Agent' => config('geohub.wikimedia_user_agent'),
            ])->get($url_encoded);

            if (! $imageResponse->successful() || empty($imageResponse->body())) {
                $this->logChannel->error('Error downloading OSF media from Wikimedia Commons: HTTP status '.$imageResponse->status());

                return $tags;
            }

            $contents = $imageResponse->body();
            $basename = explode('.', basename($url_encoded));
            $s3_osfmedia = Storage::disk($storage_name);
            $osf_name_tmp = sha1($basename[0]).'.'.$basename[1];
            $s3_osfmedia->put($osf_name_tmp, $contents);

            $this->logChannel->info('Saved OSF Media with name: '.$osf_name_tmp);
            $tags['url'] = ($s3_osfmedia->exists($osf_name_tmp)) ? $osf_name_tmp : '';
        } catch (Exception $e) {
            echo $e;
            $this->logChannel->error('Saving media in s3-osfmedia error:'.$e);
        }
```

- [x] **Step 4: Esegui il test per verificare che passi**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=PrepareMediaTagsJsonTest`
Expected: PASS. Esegui anche l'intera suite `OutSourceImporter` per verificare l'assenza di regressioni: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=OutSourceImporter`

- [x] **Step 5: Commit**

```bash
git add app/Classes/OutSourceImporter/OutSourceImporterFeatureOSMPoi.php tests/Feature/OutSourceImporter/OutSourceImporterFeatureOSMPoi/PrepareMediaTagsJsonTest.php
git commit -m "fix(oc:8361): use proper User-Agent for Wikimedia media download in initial OSM POI import"
```

---

### Task 7: Verifica di rollout (operativa, nessun commit)

**Files:** nessuno — task operativo di verifica su ambiente locale/staging prima del merge, secondo il runbook descritto in `overview.md`.

- [x] **Step 1: Eseguire la suite completa dei test del comando**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=UpdatePOIFromOsmTest`
Expected: PASS su tutti i test (Task 1-5).

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan test --filter=PrepareMediaTagsJsonTest`
Expected: PASS (Task 6).

- [x] **Step 2: Eseguire `--dry-run` sul caso concreto del ticket**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan geohub:update_pois_from_osm caiparma@webmapp.it --ec_poi_id=102105 --dry-run`
Expected: output `[dry-run] Feature image for poi ... would be updated - current: File:It-pr-ldpB072.jpg -> new: File:It-pr-ldpB072v2.jpg` (o filename analoghi, verifica il POI reale al momento dell'esecuzione).

- [x] **Step 3: Eseguire il run reale sul solo POI 102105 e verificare il risultato**

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan geohub:update_pois_from_osm caiparma@webmapp.it --ec_poi_id=102105`
Verificare via query diretta che `ec_media` 102071 (o il nuovo id se ricreato) punti al file aggiornato:

```bash
docker exec php_geohub php artisan tinker --execute="echo App\Models\EcPoi::find(102105)->featureImage->url;"
```

Expected: l'URL contiene `It-pr-ldpB072v2.jpg`, non più `It-pr-ldpB072.jpg`.

- [ ] **Step 4: Eseguire `--dry-run` su entrambi gli utenti schedulati e ispezionare il volume di update segnalati** — non ancora completato: un primo `--dry-run` su `caiparma@webmapp.it` è stato avviato e poi interrotto manualmente a ~20% (524/2579 POI, 1 solo aggiornamento reale rilevato); un secondo test, questa volta con il comando reale (non `--dry-run`) su `caiparma@webmapp.it`, è stato lanciato successivamente in locale su richiesta del dev — vedi `notes.md` per l'esito. `caipontedera@webmapp.it` non ancora testato.

Run: `docker exec -w /var/www/html/geohub php_geohub php artisan geohub:update_pois_from_osm caiparma@webmapp.it --dry-run > /tmp/dry-run-caiparma.log`
Run: `docker exec -w /var/www/html/geohub php_geohub php artisan geohub:update_pois_from_osm caipontedera@webmapp.it --dry-run > /tmp/dry-run-caipontedera.log`

Contare le righe `[dry-run]` in ciascun log e applicare il runbook descritto in `overview.md` (sezione Rischi): pochi risultati → procedere con il run reale anche via cron; molti risultati → ispezionare a campione se i filename "vecchi" seguono il pattern legacy `sha1(...)` (caso noto, procedere con run manuale presidiato) oppure sembrano un difetto del criterio (bloccare e correggere prima del merge).

- [ ] **Step 5: Documentare l'esito in notes.md** — da fare al termine dello Step 4

Aggiorna `docs/features/8361-fix-aggiornamento-featured-image-osm-wikimedia/notes.md`, sezione "Decisioni", con l'esito concreto del dry-run (quanti POI segnalati per ciascun utente, se il pattern legacy è stato riscontrato) e la decisione presa per il rollout finale (cron invariato vs run manuale presidiato una tantum).
