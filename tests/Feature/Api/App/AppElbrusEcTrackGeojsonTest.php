<?php

namespace Tests\Feature\Api\App;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\TaxonomyWhere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppElbrusEcTrackGeojsonTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testNoAppAndNoTrackReturns404()
    {
        $result = $this->getJson('/api/app/elbrus/0/geojson/ec_track_0.geojson', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testAppAndNoTrackReturns404()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_0.geojson', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testNoAppTrackReturns404()
    {
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/0/geojson/ec_track_' . $track->id . '.geojson', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testAppAndTrackReturns200()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.geojson', []);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testMappingUnderscoreAndColon()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.geojson', []);
        $this->assertEquals(200, $result->getStatusCode());

        // test response is geojson
        $geojson = json_decode($result->content(), true);
        $this->assertEquals('Feature', $geojson['type']);
        $this->assertTrue(isset($geojson['properties']));
        $this->assertTrue(isset($geojson['geometry']));

        // test fields with colon ":"
        // TO BE MAPPED: contact_phone, contact_email,
        $this->assertEquals($track->ele_from, $geojson['properties']['ele:from']);
        $this->assertEquals($track->ele_to, $geojson['properties']['ele:to']);
        $this->assertEquals($track->ele_min, $geojson['properties']['ele:min']);
        $this->assertEquals($track->ele_max, $geojson['properties']['ele:max']);
        $this->assertEquals($track->duration_forward, $geojson['properties']['duration:forward']);
        $this->assertEquals($track->duration_backward, $geojson['properties']['duration:backward']);
        $this->assertEquals($track->ascent, $geojson['properties']['ascent']);
    }

    public function testSpecialIdField()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.geojson', []);
        $this->assertEquals(200, $result->getStatusCode());

        // test response is geojson
        $geojson = json_decode($result->content(), true);
        $this->assertEquals('ec_track_' . $track->id, $geojson['properties']['id']);
    }

    public function testTaxonomyFieldWithActivity()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track->taxonomyActivities()->attach($activity->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.geojson', []);
        $geojson = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('activity_' . $activity->id, $geojson['properties']['taxonomy']['activity'][0]);
    }

    public function testTaxonomyFieldWithTwoActivity()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track->taxonomyActivities()->attach($activity->id);
        $activity1 = TaxonomyActivity::factory()->create();
        $track->taxonomyActivities()->attach($activity1->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.geojson', []);
        $geojson = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue(in_array('activity_' . $activity->id, $geojson['properties']['taxonomy']['activity']));
        $this->assertTrue(in_array('activity_' . $activity1->id, $geojson['properties']['taxonomy']['activity']));
    }

    public function testTaxonomyFieldWithTheme()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $theme = TaxonomyTheme::factory()->create();
        $track->taxonomyThemes()->attach($theme->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.geojson', []);
        $geojson = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('theme_' . $theme->id, $geojson['properties']['taxonomy']['theme'][0]);
    }

    public function testTaxonomyFieldWithAllTaxonomies()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();

        $activity = TaxonomyActivity::factory()->create();
        $track->taxonomyActivities()->attach($activity->id);

        $theme = TaxonomyTheme::factory()->create();
        $track->taxonomyThemes()->attach($theme->id);

        $who = TaxonomyTarget::factory()->create();
        $track->taxonomyTargets()->attach($who->id);

        $when = TaxonomyWhen::factory()->create();
        $track->taxonomyWhens()->attach($when->id);

        $where = TaxonomyWhere::factory()->create();
        $track->taxonomyWheres()->attach($where->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.geojson', []);
        $geojson = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('activity_' . $activity->id, $geojson['properties']['taxonomy']['activity'][0]);
        $this->assertEquals('theme_' . $theme->id, $geojson['properties']['taxonomy']['theme'][0]);
        $this->assertEquals('who_' . $who->id, $geojson['properties']['taxonomy']['who'][0]);
        $this->assertEquals('when_' . $when->id, $geojson['properties']['taxonomy']['when'][0]);
        $this->assertEquals('where_' . $where->id, $geojson['properties']['taxonomy']['where'][0]);
    }

    public function testFeatureImageWithImage()
    {

        $media = EcMedia::factory()->create();
        $api_url = route('api.ec.media.geojson', ['id' => $media->id], true);

        $ecTrack = EcTrack::factory()->create();
        $ecTrack->featureImage()->associate($media);
        $ecTrack->save();

        $app = App::factory()->create();
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.geojson', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertArrayHasKey('image', $properties);
        $this->assertIsArray($properties['image']);
        $image = $properties['image'];

        $this->assertArrayHasKey('id', $image);
        $this->assertArrayHasKey('url', $image);
        $this->assertArrayHasKey('api_url', $image);
        $this->assertArrayHasKey('caption', $image);
        $this->assertArrayHasKey('sizes', $image);

        $this->assertEquals($media->id, $image['id']);
        $this->assertEquals($media->description, $image['caption']);
        $this->assertEquals($media->url, $image['url']);
        $this->assertEquals($api_url, $image['api_url']);

        // SIZES
        $this->assertIsArray($image['sizes']);
        $this->assertCount(4, $image['sizes']);

        $this->assertArrayHasKey('108x137', $image['sizes']);
        $this->assertArrayHasKey('108x148', $image['sizes']);
        $this->assertArrayHasKey('100x200', $image['sizes']);
        $this->assertArrayHasKey('original', $image['sizes']);
    }

    public function testFeatureImageWithoutImage()
    {
        $ecTrack = EcTrack::factory()->create();

        $app = App::factory()->create();
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.geojson', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);

        $this->assertArrayNotHasKey('image', $properties);
    }

    public function testGpxField()
    {
        $ecTrack = EcTrack::factory()->create();

        $app = App::factory()->create();
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.geojson', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertIsArray($json);

        $this->assertArrayHasKey('properties', $json);
        $json = $json['properties'];

        $this->assertArrayHasKey('gpx_url', $json);
        $this->assertIsString($json['gpx_url']);
        $this->assertStringContainsString('http', $json['gpx_url']);
        $this->assertStringContainsString($ecTrack->id, $json['gpx_url']);
        $this->assertStringContainsString('download', $json['gpx_url']);
        $this->assertStringContainsString('.gpx', $json['gpx_url']);
    }

    public function testKmlField()
    {
        $ecTrack = EcTrack::factory()->create();

        $app = App::factory()->create();
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.geojson', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertIsArray($json);

        $this->assertArrayHasKey('properties', $json);
        $json = $json['properties'];


        $this->assertArrayHasKey('kml_url', $json);
        $this->assertIsString($json['kml_url']);
        $this->assertStringContainsString('http', $json['kml_url']);
        $this->assertStringContainsString($ecTrack->id, $json['kml_url']);
        $this->assertStringContainsString('download', $json['kml_url']);
        $this->assertStringContainsString('.kml', $json['kml_url']);
    }

    /**
     * @test
     */
    public function check_that_api_for_elbrus_track_has_related_poi_section_with_all_associated_pois()
    {
        $user = User::factory()->create();
        $app = App::factory()->create([
            'user_id' => $user->id,
        ]);
        $track = EcTrack::factory()->create([
            'user_id' => $user->id,
        ]);
        $pois = EcPoi::factory(10)->create([
            'user_id' => $user->id,
        ]);
        $track->ecPois()->attach($pois);

        $this->assertCount(10, $track->ecPois()->get());

        $response = $this->get(route("api.app.elbrus.geojson.track", ['app_id' => $app->id, 'track_id' => $track->id]));
        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $properties = $json['properties'];
        $this->assertIsArray($properties);
        $this->assertArrayHasKey('related', $properties);
        $this->assertArrayHasKey('poi', $properties['related']);
        $this->assertArrayHasKey('related', $properties['related']['poi']);
        $this->assertEquals(3, $properties['related']['poi']['related'][2]);
    }

    /**
     * @test
     */
    public function check_that_api_for_elbrus_taxonomy_poi_type_has_poi_types_derived_from_related_pois()
    {
        $user = User::factory()->create();
        $app = App::factory()->create([
            'user_id' => $user->id,
        ]);

        $poiTypesSerie1 = TaxonomyPoiType::factory()->create();
        $poiTypesSerie2 = TaxonomyPoiType::factory(2)->create();
        $poiTypesSerie3 = TaxonomyPoiType::factory(3)->create();

        // $poiTypes = TaxonomyPoiType::all();
        $pois = EcPoi::factory(3)->create([
            'user_id' => $user->id,
        ]);
        
        EcPoi::find(1)->taxonomyPoiTypes()->attach($poiTypesSerie1);
        EcPoi::find(2)->taxonomyPoiTypes()->attach($poiTypesSerie2);
        EcPoi::find(3)->taxonomyPoiTypes()->attach($poiTypesSerie3);

        $track = EcTrack::factory()->create([
            'user_id' => $user->id,
        ]);

        $track->ecPois()->attach($pois);

        $this->assertCount(3, $track->ecPois()->get());

        $response = $this->get(route("api.app.elbrus.taxonomies", ['app_id' => $app->id, 'taxonomy_name' => 'webmapp_category']));
        $content = $response->getContent();
        $this->assertJson($content);
        $json = $response->json();

        $this->assertIsArray($json);
        $this->assertCount(6, $json);
        $this->assertArrayHasKey('webmapp_category_1', $json);
        $this->assertEquals($poiTypesSerie1->name, $json['webmapp_category_1']['name']['it']);
    }
}
