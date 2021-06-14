<?php

namespace Tests\Feature\Api\App;

use App\Models\App;
use App\Models\EcPoi;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\TaxonomyWhere;
use App\Models\TaxonomyPoiType;
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
    public function testSpecialIdField() {
        $app = App::factory()->create();
        $poi = EcPoi::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_poi_' . $poi->id . '.geojson', []);
        $this->assertEquals(200, $result->getStatusCode());

        // test response is geojson
        $geojson = json_decode($result->content(), true);
        $this->assertEquals('ec_poi_'.$poi->id,$geojson['properties']['id']);
    }
    public function testTaxonomyFieldWithActivity() {
        $app = App::factory()->create();
        $poi = EcPoi::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $poi->taxonomyActivities()->attach($activity->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_poi_' . $poi->id . '.geojson', []);
        $geojson = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('activity_'.$activity->id,$geojson['properties']['taxonomy']['activity'][0]);

    }
    public function testTaxonomyFieldWithTwoActivity() {
        $app = App::factory()->create();
        $poi = EcPoi::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $poi->taxonomyActivities()->attach($activity->id);
        $activity1 = TaxonomyActivity::factory()->create();
        $poi->taxonomyActivities()->attach($activity1->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_poi_' . $poi->id . '.geojson', []);
        $geojson = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue(in_array('activity_'.$activity->id,$geojson['properties']['taxonomy']['activity']));
        $this->assertTrue(in_array('activity_'.$activity1->id,$geojson['properties']['taxonomy']['activity']));

    }
    public function testTaxonomyFieldWithTheme() {
        $app = App::factory()->create();
        $poi = EcPoi::factory()->create();
        $theme = TaxonomyTheme::factory()->create();
        $poi->taxonomyThemes()->attach($theme->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_poi_' . $poi->id . '.geojson', []);
        $geojson = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('theme_'.$theme->id,$geojson['properties']['taxonomy']['theme'][0]);

    }
    public function testTaxonomyFieldWithAllTaxonomies() {
        $app = App::factory()->create();
        $poi = EcPoi::factory()->create();

        $activity = TaxonomyActivity::factory()->create();
        $poi->taxonomyActivities()->attach($activity->id);

        $theme = TaxonomyTheme::factory()->create();
        $poi->taxonomyThemes()->attach($theme->id);

        $who = TaxonomyTarget::factory()->create();
        $poi->taxonomyTargets()->attach($who->id);

        $when = TaxonomyWhen::factory()->create();
        $poi->taxonomyWhens()->attach($when->id);

        $where = TaxonomyWhere::factory()->create();
        $poi->taxonomyWheres()->attach($where->id);

        $poi_type = TaxonomyPoiType::factory()->create();
        $poi->taxonomyPoiTypes()->attach($poi_type->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_poi_' . $poi->id . '.geojson', []);
        $geojson = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('activity_'.$activity->id,$geojson['properties']['taxonomy']['activity'][0]);
        $this->assertEquals('theme_'.$theme->id,$geojson['properties']['taxonomy']['theme'][0]);
        $this->assertEquals('who_'.$who->id,$geojson['properties']['taxonomy']['who'][0]);
        $this->assertEquals('when_'.$when->id,$geojson['properties']['taxonomy']['when'][0]);
        $this->assertEquals('where_'.$where->id,$geojson['properties']['taxonomy']['where'][0]);
        $this->assertEquals('webmapp_category_'.$poi_type->id,$geojson['properties']['taxonomy']['webmapp_category'][0]);

    }
}
