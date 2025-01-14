<?php

namespace Tests\Feature\Api\App;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppElbrusConfigJsonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    /**
     * Test 404 with not found app onject
     */
    public function test_no_id_return_code404()
    {
        $result = $this->getJson('/api/app/elbrus/0/config.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    /**
     * Test code 200 for existing app
     */
    public function test_existing_app_returns200()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
    }

    /**
     * Test name, id, customerName API
     * config.json example https://k.webmapp.it/caipontedera/config.json
     */
    public function test_section_app()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->APP));
        $this->assertEquals($app->app_id, $json->APP->id);
        $this->assertEquals($app->name, $json->APP->name);
        $this->assertEquals($app->customer_name, $json->APP->customerName);
    }

    /**
     * Test minZoom, maxZoom, defZoom in MAP section
     */
    public function test_section_map_zoom()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP));
        $this->assertEquals($app->map_max_zoom, $json->MAP->maxZoom);
        $this->assertEquals($app->map_def_zoom, $json->MAP->defZoom);
        $this->assertEquals($app->map_min_zoom, $json->MAP->minZoom);
    }

    /**
     * Test layers in MAP section
     */
    public function test_section_map_layers()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP->layers));
        $this->assertIsArray($json->MAP->layers);
        $this->assertEquals('Mappa', $json->MAP->layers[0]->label);
        $this->assertEquals('maptile', $json->MAP->layers[0]->type);
        $this->assertEquals('https://api.webmapp.it/tiles/', $json->MAP->layers[0]->tilesUrl);
    }

    /**
     * Test REPORT section
     *   "REPORTS": {
     * "enable": true,
     * "url": "https://geohub.webmapp.it/api/usergenerateddata/store",
     * "items": [
     * {
     * "title": "Crea un nuovo waypoint",
     * "success": "Waypoint creato con successo",
     * "url": "https://geohub.webmapp.it/api/usergenerateddata/store",
     * "type": "geohub",
     * "fields": [
     * {
     * "label": "Nome",
     * "name": "title",
     * "mandatory": true,
     * "type": "text",
     * "placeholder": "Scrivi qua il nome del waypoint"
     * },
     * {
     * "label": "Descrizione",
     * "name": "description",
     * "mandatory": true,
     * "type": "textarea",
     * "placeholder": "Descrivi brevemente il waypoint"
     * },
     * {
     * "label": "Foto",
     * "name": "gallery",
     * "mandatory": false,
     * "type": "gallery",
     * "limit": 5,
     * "placeholder": "Aggiungi qualche foto descrittiva del waypoint"
     * }
     * ]
     * }
     * ]
     * }
     */
    public function test_section_reports()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->REPORTS));
        $this->assertTrue($json->REPORTS->enable);
        $this->assertEquals('https://geohub.webmapp.it/api/usergenerateddata/store', $json->REPORTS->url);
        $this->assertIsArray($json->REPORTS->items);
        $this->assertCount(1, $json->REPORTS->items);
    }

    /**
     * Test GEOLOCATION section
     *
     *
     * "GEOLOCATION": {
     * "record": {
     * "enable": true,
     * "export": true,
     * "uploadUrl": "https://geohub.webmapp.it/api/usergenerateddata/store"
     * }
     * }
     */
    public function test_section_geolocation()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->GEOLOCATION));
        $this->assertTrue(isset($json->GEOLOCATION->record));
        $this->assertFalse($json->GEOLOCATION->record->enable);
        $this->assertTrue($json->GEOLOCATION->record->export);
        $this->assertEquals('https://geohub.webmapp.it/api/usergenerateddata/store', $json->GEOLOCATION->record->uploadUrl);
    }

    /**
     * Test AUTH section
     *
     * "AUTH": {
     * "enable": true,
     * "loginToGeohub": true
     * }
     */
    public function test_section_auth()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->AUTH));
        $this->assertTrue($json->AUTH->enable);
        $this->assertTrue($json->AUTH->loginToGeohub);
    }

    /**
     * Test THEME section
     */
    public function test_section_theme()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->THEME));
        $this->assertEquals($app->font_family_header, $json->THEME->fontFamilyHeader);
        $this->assertEquals($app->font_family_content, $json->THEME->fontFamilyContent);
        $this->assertEquals($app->default_feature_color, $json->THEME->defaultFeatureColor);
        $this->assertEquals($app->primary_color, $json->THEME->primary);
    }

    /**
     * Test OPTIONS section
     */
    public function test_section_options()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->OPTIONS));
        $fields = [
            'start_url' => 'startUrl',
            'show_edit_link' => 'showEditLink',
            'skip_route_index_download' => 'skipRouteIndexDownload',
            'show_track_ref_label' => 'showTrackRefLabel',

        ];
        foreach ($fields as $key => $field) {
            $this->assertEquals($app->$key, $json->OPTIONS->$field);
        }
        $this->assertEquals('https://geohub.webmapp.it/api/app/elbrus/'.$app->id.'/', $json->OPTIONS->baseUrl);
    }

    /**
     * Test TABLES section
     */
    public function test_section_tables()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->TABLES));
        $this->assertTrue(isset($json->TABLES->details));
        $fields = [
            'table_details_show_gpx_download' => 'showGpxDownload',
            'table_details_show_kml_download' => 'showKmlDownload',
            'table_details_show_related_poi' => 'showRelatedPoi',
            'table_details_show_geojson_download' => 'showGeojsonDownload',
            'table_details_show_shapefile_download' => 'showShapefileDownload',
        ];
        $invertedFields = [
            'table_details_show_duration_forward' => 'hide_duration:forward',
            'table_details_show_duration_backward' => 'hide_duration:backward',
            'table_details_show_distance' => 'hide_distance',
            'table_details_show_ascent' => 'hide_ascent',
            'table_details_show_descent' => 'hide_descent',
            'table_details_show_ele_max' => 'hide_ele:max',
            'table_details_show_ele_min' => 'hide_ele:min',
            'table_details_show_ele_from' => 'hide_ele:from',
            'table_details_show_ele_to' => 'hide_ele:to',
            'table_details_show_scale' => 'hide_scale',
            'table_details_show_cai_scale' => 'hide_cai_scale',
            'table_details_show_mtb_scale' => 'hide_mtb_scale',
            'table_details_show_ref' => 'hide_ref',
            'table_details_show_surface' => 'hide_surface',
        ];
        foreach ($fields as $modelField => $field) {
            $this->assertEquals($app->$modelField, $json->TABLES->details->$field);
        }
        foreach ($invertedFields as $modelField => $field) {
            $this->assertEquals($app->$modelField, ! $json->TABLES->details->$field);
        }
    }

    /**
     * Test ROUTING section enabled
     */
    public function test_routing_section_enabled()
    {
        $app = App::factory()->create(['enable_routing' => true]);
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->ROUTING));
        $this->assertTrue($json->ROUTING->enable);
    }

    /**
     * Test ROUTING section disabled
     */
    public function test_routing_section_disabled()
    {
        $app = App::factory()->create(['enable_routing' => false]);
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->ROUTING));
        $this->assertFalse($json->ROUTING->enable);
    }

    public function test_b_box_with_one_track_square()
    {
        $user = User::factory()->create();
        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();
        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(0 0 0, 10 10 0)'))")]);
        $track->user_id = $user->id;
        $track->save();

        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP->bbox));
        $this->assertEquals(0, $json->MAP->bbox[0]);
        $this->assertEquals(0, $json->MAP->bbox[1]);
        $this->assertEquals(10, $json->MAP->bbox[2]);
        $this->assertEquals(10, $json->MAP->bbox[3]);
    }

    public function test_b_box_with_one_track_rectangle()
    {
        $user = User::factory()->create();
        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();
        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(1 0 0, 3 4 0)'))")]);
        $track->user_id = $user->id;
        $track->save();

        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP->bbox));
        $this->assertEquals(1, $json->MAP->bbox[0]);
        $this->assertEquals(0, $json->MAP->bbox[1]);
        $this->assertEquals(3, $json->MAP->bbox[2]);
        $this->assertEquals(4, $json->MAP->bbox[3]);
    }

    public function test_b_box_with_two_track()
    {
        $user = User::factory()->create();
        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();
        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(0 0 0, 1 1 0)'))")]);
        $track->user_id = $user->id;
        $track->save();
        $track1 = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(2 2 0, 3 3 0)'))")]);
        $track1->user_id = $user->id;
        $track1->save();

        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP->bbox));
        $this->assertEquals(0, $json->MAP->bbox[0]);
        $this->assertEquals(0, $json->MAP->bbox[1]);
        $this->assertEquals(3, $json->MAP->bbox[2]);
        $this->assertEquals(3, $json->MAP->bbox[3]);
    }

    /**
     * @test
     */
    public function check_if_section_external_overlays_exists()
    {
        $external_overlays = <<<'EXTERNAL_OVERLAYS'
[
    {
        "id": "punti_acqua",
        "type": "geojson",
        "geojsonUrl": "punti_acqua.geojson",
        "color": "#3EAFE3",
        "icon": "wm-icon-waterdrop",
        "noDetails": false,
        "name": "Punti acqua",
        "locale": "it",
        "createTaxonomy": "webmapp_category"
        },
        {
        "id": "segnaletica_verticale",
        "type": "geojson",
        "geojsonUrl": "segnaletica_verticale.geojson",
        "color": "#e67300",
        "icon": "wm-icon-guidepost-15",
        "noDetails": false,
        "name": "Segnaletica verticale",
        "locale": "it",
        "createTaxonomy": "webmapp_category"
    }
]
EXTERNAL_OVERLAYS;

        $user = User::factory()->create();
        $app = App::factory()->create([
            'user_id' => $user->id,
            'external_overlays' => $external_overlays,
        ]);

        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(1 0 0, 3 4 0)'))")]);
        $track->user_id = $user->id;
        $track->save();

        $response = $this->get(route('api.app.elbrus.config', ['id' => $app->id]));
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getContent());

        $this->assertTrue(isset($json->MAP));
        $this->assertTrue(isset($json->MAP->overlays));
        $this->assertJson($external_overlays, json_encode($json->MAP->overlays));
    }

    /**
     * @test
     */
    public function check_languages_section_with_only_default_set()
    {
        $user = User::factory()->create();
        $app = App::factory()->create([
            'user_id' => $user->id,
        ]);

        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(1 0 0, 3 4 0)'))")]);
        $track->user_id = $user->id;
        $track->save();

        $response = $this->get(route('api.app.elbrus.config', ['id' => $app->id]));
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getContent());

        $this->assertTrue(isset($json->LANGUAGES));
        $this->assertTrue(isset($json->LANGUAGES->default));
        $this->assertIsString($json->LANGUAGES->default);
        $this->assertSame('it', $json->LANGUAGES->default);
        $this->assertFalse(isset($json->LANGUAGES->available));
    }

    /**
     * @test
     */
    public function check_languages_section_with_available_languages_set()
    {
        $user = User::factory()->create();
        $app = App::factory()->create([
            'user_id' => $user->id,
            'default_language' => 'en',
            'available_languages' => json_encode(['it', 'en']),
        ]);

        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(1 0 0, 3 4 0)'))")]);
        $track->user_id = $user->id;
        $track->save();

        $response = $this->get(route('api.app.elbrus.config', ['id' => $app->id]));
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getContent());

        $this->assertTrue(isset($json->LANGUAGES));
        $this->assertTrue(isset($json->LANGUAGES->default));
        $this->assertIsString($json->LANGUAGES->default);
        $this->assertSame('en', $json->LANGUAGES->default);
        $this->assertTrue(isset($json->LANGUAGES->available));
        $this->assertIsArray($json->LANGUAGES->available);
        $this->assertCount(2, $json->LANGUAGES->available);
        $this->assertTrue(in_array('it', $json->LANGUAGES->available));
        $this->assertTrue(in_array('en', $json->LANGUAGES->available));
    }

    private function _getJsonfromAPP($app)
    {
        $user = User::factory()->create();
        $app = App::factory()->create($app);
        $app->user_id = $user->id;
        $app->save();
        $response = $this->get(route('api.app.elbrus.config', ['id' => $app->id]));
        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getContent());
    }

    /**
     * @test
     */
    public function check_auth_show_at_startup_when_field_is_true()
    {
        $json = $this->_getJsonfromAPP(['auth_show_at_startup' => true]);
        $this->assertSame(true, $json->AUTH->showAtStartup);
    }

    /**
     * @test
     */
    public function check_auth_show_at_startup_when_field_is_false()
    {
        $json = $this->_getJsonfromAPP(['auth_show_at_startup' => false]);
        $this->assertSame(false, $json->AUTH->showAtStartup);
    }

    /**
     * @test
     */
    public function check_offline_enable_when_field_is_true()
    {
        $json = $this->_getJsonfromAPP(['offline_enable' => true]);
        $this->assertSame(true, $json->OFFLINE->enable);
    }

    /**
     * @test
     */
    public function check_offline_enable_when_field_is_false()
    {
        $json = $this->_getJsonfromAPP(['offline_enable' => false]);
        $this->assertSame(false, $json->OFFLINE->enable);
    }

    /**
     * @test
     */
    public function check_offline_force_auth_when_field_is_true()
    {
        $json = $this->_getJsonfromAPP(['offline_force_auth' => true]);
        $this->assertSame(true, $json->OFFLINE->forceAuth);
    }

    /**
     * @test
     */
    public function check_offline_force_auth_when_field_is_false()
    {
        $json = $this->_getJsonfromAPP(['offline_force_auth' => false]);
        $this->assertSame(false, $json->OFFLINE->forceAuth);
    }

    /**
     * @test
     */
    public function check_geolocation_record_enable_when_field_is_true()
    {
        $json = $this->_getJsonfromAPP(['geolocation_record_enable' => true]);
        $this->assertSame(true, $json->GEOLOCATION->record->enable);
    }

    /**
     * @test
     */
    public function check_geolocation_record_enable_when_field_is_false()
    {
        $json = $this->_getJsonfromAPP(['geolocation_record_enable' => false]);
        $this->assertSame(false, $json->GEOLOCATION->record->enable);
    }
}
