<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\App;
use Tests\TestCase;

class AppElbrusConfigJsonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 404 with not found app onject
     */
    public function testNoIdReturnCode404()
    {
        $result = $this->getJson('/api/app/elbrus/0/config.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    /**
     * Test code 200 for existing app
     */
    public function testExistingAppReturns200()
    {
        $app=App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());

    }

    /**
     * Test name, id, customerName API
     * config.json example https://k.webmapp.it/caipontedera/config.json
     */
    public function testSectionApp()
    {
        $app=App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->APP));
        $this->assertEquals($app->app_id,$json->APP->id);
        $this->assertEquals($app->name,$json->APP->name);
        $this->assertEquals($app->customerName,$json->APP->customerName);

    }

    /**
     * Test minZoom, maxZoom, defZoom in MAP section
     */
    public function testSectionMapZoom() {

        $app=App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP));
        $this->assertEquals($app->maxZoom,$json->MAP->maxZoom);
        $this->assertEquals($app->defZoom,$json->MAP->defZoom);
        $this->assertEquals($app->minZoom,$json->MAP->minZoom);

    }

    /**
     * Test layers in MAP section
     */
    public function testSectionMapLayers() {

        $app=App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->MAP->layers));
        $this->assertEquals('Mappa',$json->MAP->layers->label);
        $this->assertEquals('maptile',$json->MAP->layers->type);
        $this->assertEquals('https://api.webmapp.it/tiles/',$json->MAP->layers->tilesUrl);
    }

    /**
     * Test THEME section
     */
    public function testSectionTheme() {

        $app=App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->THEME));
        $this->assertEquals($app->fontFamilyHeader,$json->THEME->fontFamilyHeader);
        $this->assertEquals($app->fontFamilyContent,$json->THEME->fontFamilyContent);
        $this->assertEquals($app->defaultFeatureColor,$json->THEME->defaultFeatureColor);
        $this->assertEquals($app->primary,$json->THEME->primary);

    }

    /**
     * Test OPTIONS section
     */
    public function testSectionOptions() {

        $app=App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
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
        foreach($fields as $field) {
            $this->assertEquals($app->$field,$json->OPTIONS->$field);
        }
        $this->assertEquals('https://geohub.webmapp.it/api/app/elbrus/'.$app->id.'/',$json->OPTIONS->baseUrl);
    }

    /**
     * Test TABLES section
     */
    public function testSectionTables() {

        $app=App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->TABLES));
        $this->assertTrue(isset($json->TABLES->details));
        $fields = [
            'showGpxDownload',
            'showKmlDownload',
            'showRelatedPoi',
        ];
        foreach($fields as $field) {
            $this->assertEquals($app->$field,$json->TABLES->details->$field);
        }
    }

}
