<?php

namespace Tests\Feature\Api\App;

use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\App;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppElbrusConfigJsonTest extends TestCase {
    use RefreshDatabase;

    /**
     * Test 404 with not found app onject
     */
    public function testNoIdReturnCode404() {
        $result = $this->getJson('/api/app/elbrus/0/config.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    /**
     * Test code 200 for existing app
     */
    public function testExistingAppReturns200() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
    }

    /**
     * Test name, id, customerName API
     * config.json example https://k.webmapp.it/caipontedera/config.json
     */
    public function testSectionApp() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->APP));
        $this->assertEquals($app->app_id, $json->APP->id);
        $this->assertEquals($app->name, $json->APP->name);
        $this->assertEquals($app->customerName, $json->APP->customerName);
    }

    /**
     * Test minZoom, maxZoom, defZoom in MAP section
     */
    public function testSectionMapZoom() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP));
        $this->assertEquals($app->maxZoom, $json->MAP->maxZoom);
        $this->assertEquals($app->defZoom, $json->MAP->defZoom);
        $this->assertEquals($app->minZoom, $json->MAP->minZoom);
    }

    /**
     * Test layers in MAP section
     */
    public function testSectionMapLayers() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
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
    public function testSectionReports() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
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
    public function testSectionGeolocation() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->GEOLOCATION));
        $this->assertTrue(isset($json->GEOLOCATION->record));
        $this->assertTrue($json->GEOLOCATION->record->enable);
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
     *
     */
    public function testSectionAuth() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->AUTH));
        $this->assertTrue($json->AUTH->enable);
        $this->assertTrue($json->AUTH->loginToGeohub);
    }

    /**
     * Test THEME section
     */
    public function testSectionTheme() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->THEME));
        $this->assertEquals($app->fontFamilyHeader, $json->THEME->fontFamilyHeader);
        $this->assertEquals($app->fontFamilyContent, $json->THEME->fontFamilyContent);
        $this->assertEquals($app->defaultFeatureColor, $json->THEME->defaultFeatureColor);
        $this->assertEquals($app->primary, $json->THEME->primary);
    }

    /**
     * Test OPTIONS section
     */
    public function testSectionOptions() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->OPTIONS));
        $fields = [
            'startUrl',
            'showEditLink',
            'skipRouteIndexDownload',
            'poiMinRadius',
            'poiMaxRadius',
            'poiIconZoom',
            'poiIconRadius',
            'poiMinZoom',
            'poiLabelMinZoom',
            'showTrackRefLabel',
        ];
        foreach ($fields as $field) {
            $this->assertEquals($app->$field, $json->OPTIONS->$field);
        }
        $this->assertEquals('https://geohub.webmapp.it/api/app/elbrus/' . $app->id . '/', $json->OPTIONS->baseUrl);
    }

    /**
     * Test TABLES section
     */
    public function testSectionTables() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->TABLES));
        $this->assertTrue(isset($json->TABLES->details));
        $fields = [
            'showGpxDownload',
            'showKmlDownload',
            'showRelatedPoi',
        ];
        foreach ($fields as $field) {
            $this->assertEquals($app->$field, $json->TABLES->details->$field);
        }
    }

    /**
     * Test ROUTING section enabled
     */
    public function testRoutingSectionEnabled() {
        $app = App::factory()->create(['enableRouting' => true]);
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->ROUTING));
        $this->assertTrue($json->ROUTING->enable);
    }

    /**
     * Test ROUTING section disabled
     */
    public function testRoutingSectionDisabled() {
        $app = App::factory()->create(['enableRouting' => false]);
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->ROUTING));
        $this->assertFalse($json->ROUTING->enable);
    }

    public function testBBoxWithOneTrackSquare() {
        $user = User::factory()->create();
        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();
        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(0 0 0, 10 10 0)'))")]);
        $track->user_id = $user->id;
        $track->save();

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP->bbox));
        $this->assertEquals(0, $json->MAP->bbox[0]);
        $this->assertEquals(0, $json->MAP->bbox[1]);
        $this->assertEquals(10, $json->MAP->bbox[2]);
        $this->assertEquals(10, $json->MAP->bbox[3]);
    }

    public function testBBoxWithOneTrackRectangle() {
        $user = User::factory()->create();
        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();
        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(1 0 0, 3 4 0)'))")]);
        $track->user_id = $user->id;
        $track->save();

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP->bbox));
        $this->assertEquals(1, $json->MAP->bbox[0]);
        $this->assertEquals(0, $json->MAP->bbox[1]);
        $this->assertEquals(3, $json->MAP->bbox[2]);
        $this->assertEquals(4, $json->MAP->bbox[3]);
    }

    public function testBBoxWithTwoTrack() {
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

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/config.json', []);
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
    public function check_if_section_external_overlays_exists() {
        $external_overlays = <<<EXTERNAL_OVERLAYS
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
            'external_overlays' => $external_overlays
        ]);

        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(1 0 0, 3 4 0)'))")]);
        $track->user_id = $user->id;
        $track->save();

        $response = $this->get(route("api.app.elbrus.config", ['id' => $app->id]));
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getContent());

        $this->assertTrue(isset($json->OVERLAYS));
        $this->assertTrue(isset($json->OVERLAYS->external_overlays));
        $this->assertJson($external_overlays, $json->OVERLAYS->external_overlays);
    }

    /**
     * @test
     */
    public function check_languages_section_with_only_default_set() {
        $user = User::factory()->create();
        $app = App::factory()->create([
            'user_id' => $user->id
        ]);

        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(1 0 0, 3 4 0)'))")]);
        $track->user_id = $user->id;
        $track->save();

        $response = $this->get(route("api.app.elbrus.config", ['id' => $app->id]));
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
    public function check_languages_section_with_available_languages_set() {
        $user = User::factory()->create();
        $app = App::factory()->create([
            'user_id' => $user->id,
            'default_language' => 'en',
            'available_languages' => json_encode(['it', 'en'])
        ]);

        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(1 0 0, 3 4 0)'))")]);
        $track->user_id = $user->id;
        $track->save();

        $response = $this->get(route("api.app.elbrus.config", ['id' => $app->id]));
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


    private function _getJsonfromAPP($app){
        $user = User::factory()->create();
        $app = App::factory()->create($app);
        $app->user_id=$user->id;
        $app->save();
        $response = $this->get(route("api.app.elbrus.config", ['id' => $app->id]));
        $this->assertEquals(200, $response->getStatusCode());
        return json_decode($response->getContent());
    }

    /**
     * @test
     */
    public function check_auth_show_at_startup_when_field_is_true() {
        $json = $this->_getJsonfromAPP(['auth_show_at_startup' => true]);
        $this->assertSame(true, $json->AUTH->showAtStartup);
    }
    /**
     * @test
     */
    public function check_auth_show_at_startup_when_field_is_false() {
        $json = $this->_getJsonfromAPP(['auth_show_at_startup' => false]);
        $this->assertSame(false, $json->AUTH->showAtStartup);
    }
    /**
     * @test
     */
    public function check_offline_enable_when_field_is_true() {
        $json = $this->_getJsonfromAPP(['offline_enable' => true]);
        $this->assertSame(true, $json->OFFLINE->enable);
    }
    /**
     * @test
     */
    public function check_offline_enable_when_field_is_false() {
        $json = $this->_getJsonfromAPP(['offline_enable' => false]);
        $this->assertSame(false, $json->OFFLINE->enable);
    }
    /**
     * @test
     */
    public function check_offline_force_auth_when_field_is_true() {
        $json = $this->_getJsonfromAPP(['offline_force_auth' => true]);
        $this->assertSame(true, $json->OFFLINE->forceAuth);
    }
    /**
     * @test
     */
    public function check_offline_force_auth_when_field_is_false() {
        $json = $this->_getJsonfromAPP(['offline_force_auth' => false]);
        $this->assertSame(false, $json->OFFLINE->forceAuth);
    }
}
