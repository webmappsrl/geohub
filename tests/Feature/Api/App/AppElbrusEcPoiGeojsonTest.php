<?php

namespace Tests\Feature\Api\App;

use App\Models\App;
use App\Models\EcPoi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppElbrusEcPoiGeojsonTest extends TestCase {
    use RefreshDatabase;

    public function testNoAppAndNoPoiReturns404() {
        $result = $this->getJson('/api/app/elbrus/0/geojson/ec_poi_0.geojson', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testAppAndNoPoiReturns404() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_poi_0.geojson', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testNoAppPoiReturns404() {
        $poi = EcPoi::factory()->create();
        $result = $this->getJson('/api/app/elbrus/0/geojson/ec_poi_' . $poi->id . '.geojson', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testAppAndPoiReturns200() {
        $app = App::factory()->create();
        $poi = EcPoi::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_poi_' . $poi->id . '.geojson', []);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testMappingUnderscoreAndColon() {
        $app = App::factory()->create();
        $poi = EcPoi::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_poi_' . $poi->id . '.geojson', []);
        $this->assertEquals(200, $result->getStatusCode());

        // test response is geojson
        $geojson = json_decode($result->content(), true);
        $this->assertEquals('Feature', $geojson['type']);
        $this->assertTrue(isset($geojson['properties']));
        $this->assertTrue(isset($geojson['geometry']));

        // test fields with colon ":"
        // TO BE MAPPED: contact_phone, contact_email,
        $this->assertEquals($poi->contact_phone, $geojson['properties']['contact:phone']);
        $this->assertEquals($poi->contact_email, $geojson['properties']['contact:email']);
    }
}
