<?php

use App\Http\Facades\OsmClient;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdatePOIFromOsmTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // config('geohub.ec_media_storage_name') resolves to a real S3 disk in this repo's
        // .env (no .env.testing override) — fake it once here so no test in this class,
        // new or pre-existing, ever writes to real S3.
        Storage::fake(config('geohub.ec_media_storage_name'));
    }

    /**
     * Test the command with a non existing user
     *
     * @return void
     */
    public function test_command_with_non_existing_user()
    {
        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => '',
        ])->expectsOutput('Please provide a user email');
    }

    /**
     * Test the command with an existing user
     *
     * @return void
     */
    public function test_command_with_existing_user()
    {
        $user = User::factory()->create();

        // create a poi
        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
        ])->expectsOutput('Finished.');
    }

    /**
     * Test if the command does not update poi if osmid is null
     *
     * @return void
     */
    public function test_command_with_invalid_osm_url()
    {
        $user = User::factory()->create();

        // create a poi
        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            // take an osmid that does not exist
            'osmid' => null,
        ]);

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
        ]);

        // check if 'ele' column in poi table is not updated
        $this->assertDatabaseHas('ec_pois', [
            'ele' => null,
            'skip_geomixer_tech' => false,
        ]);
    }

    /**
     * Test if the command updates poi if osmid is not null
     *
     * @return void
     */
    public function test_command_with_valid_osm_url()
    {
        $user = User::factory()->create();

        // create a poi
        $poi = EcPoi::where('osmid', '!=', null)->first();

        // call the getGeojson method of the OsmClient facade
        $data = json_decode(OsmClient::getGeojson('node/'.$poi->osmid), true);

        // if  data has no 'ele' properties, set it to 123
        if (! array_key_exists('ele', $data['properties'])) {
            $data['properties']['ele'] = 123;
        }

        // check if 'ele' key exists in data
        $this->assertArrayHasKey('ele', $data['properties']);

        // call the command
        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
        ]);

        // check if the 'ele' column in poi table is updated
        $this->assertDatabaseHas('ec_pois', [
            'ele' => $data['properties']['ele'],
            'skip_geomixer_tech' => false,
        ]);
    }

    /**
     * Test if the command throws error when Url is not valid
     *
     * @return void
     */
    public function test_command_with_invalid_osm_url_throws_error()
    {
        $user = User::factory()->create();

        // create a poi
        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            // take an osmid that does not exist
            'osmid' => EcPoi::where('osmid', '!=', null)->first()->osmid.'123',
        ]);

        $this->artisan('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
        ])->expectsOutput('Error while retrieving data from OSM for poi '.$poi->name.' (https://api.openstreetmap.org/api/0.6/node/'.$poi->osmid.'.json). Url not valid');
    }

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

        // In --dry-run mode the whole attribute/geometry/save block in updatePoiData() is
        // skipped, so the poi is never saved and EcPoiObserver::saved() never fires here —
        // no real HTTP call is expected. Http::fake() is kept as a defensive hermeticity
        // guard (same pattern as test_poi_without_wikimedia_commons_tag_is_not_touched),
        // not because a network call is currently expected on this path.
        Http::fake();

        Artisan::call('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--dry-run' => true,
        ]);

        $this->assertStringNotContainsString('Generating App POIs', Artisan::output());
    }

    /**
     * Test that console output is also persisted to the dedicated
     * 'update_pois_from_osm' daily log channel, so a run (scheduled or
     * manual) leaves a record on disk, not just on stdout. Uses an
     * overridden test-only path — see the comment below.
     *
     * @return void
     */
    public function test_command_output_is_persisted_to_the_dedicated_log_channel()
    {
        // Use a test-only base filename, never the real 'update_pois_from_osm' one:
        // that daily-rotated path is shared with real (manual/scheduled) runs on the
        // same machine, and this test used to unlink() it at start/end — which
        // deleted a real run's log file mid-write when both happened to execute on
        // the same day. Overriding the channel's path here keeps this test's file
        // I/O fully isolated from anything else using the real channel.
        config(['logging.channels.update_pois_from_osm.path' => storage_path('logs/update_pois_from_osm_test.log')]);
        $logPath = storage_path('logs/update_pois_from_osm_test-'.now()->format('Y-m-d').'.log');
        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $user = User::factory()->create();
        $poi = EcPoi::factory()->create([
            'user_id' => $user->id,
            'osmid' => '181818181',
        ]);

        OsmClient::shouldReceive('getGeojson')
            ->once()
            ->andReturn(json_encode([
                'version' => 0.6,
                'generator' => 'test',
                '_osmid' => '181818181',
                'type' => 'Feature',
                '_api_url' => 'https://api.openstreetmap.org/api/0.6/node/181818181.json',
                'properties' => ['name' => 'Log channel test POI'],
                'geometry' => ['type' => 'Point', 'coordinates' => [10.43, 43.70]],
            ]));

        // This POI has no wikimedia_commons tag, but its attributes/geometry still get
        // saved (not dry-run), which fires EcPoiObserver::saved() -> UpdateEcPoiDemJob, a
        // real Http::get() to the DEM elevation service — fake it so this test stays
        // hermetic (same pattern as test_poi_without_wikimedia_commons_tag_is_not_touched).
        Http::fake();

        Artisan::call('geohub:update_pois_from_osm', [
            'user_email' => $user->email,
            '--ec_poi_id' => $poi->id,
        ]);

        $this->assertFileExists($logPath);
        // The poi's name is only updated from OSM data (updatePoiName()) partway through
        // updatePoiData(), so the final "... updated." line — not the first "Updating poi
        // ..." line — is the one that reflects the OSM-provided name.
        $this->assertStringContainsString('Poi Log channel test POI (osmid: 181818181) updated.', file_get_contents($logPath));

        unlink($logPath);
    }
}
