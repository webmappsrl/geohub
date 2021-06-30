<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\TaxonomyWhere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppElbrusEcTrackJsonTest extends TestCase
{
    use RefreshDatabase;

    public function testNoAppAndNoTrackReturns404() {
        $result = $this->getJson('/api/app/elbrus/0/geojson/ec_track_0.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testAppAndNoTrackReturns404() {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_0.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testNoAppTrackReturns404() {
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/0/geojson/ec_track_' . $track->id . '.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testAppAndTrackReturns200() {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testMappingUnderscoreAndColon() {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $this->assertEquals(200, $result->getStatusCode());

        // test response is geojson
        $json = json_decode($result->content(), true);

        // test fields with colon ":"
        // TO BE MAPPED: contact_phone, contact_email,
        $this->assertEquals($track->ele_from, $json['ele:from']);
        $this->assertEquals($track->ele_to, $json['ele:to']);
        $this->assertEquals($track->ele_min, $json['ele:min']);
        $this->assertEquals($track->ele_max, $json['ele:max']);
        $this->assertEquals($track->duration_forward, $json['duration:forward']);
        $this->assertEquals($track->duration_backward, $json['duration:backward']);
    }

    public function testSpecialIdField() {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $this->assertEquals(200, $result->getStatusCode());

        $json = json_decode($result->content(), true);
        $this->assertEquals('ec_track_'.$track->id,$json['id']);
    }
    public function testTaxonomyFieldWithActivity() {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track->taxonomyActivities()->attach($activity->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $json = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('activity_'.$activity->id,$json['taxonomy']['activity'][0]);

    }
    public function testTaxonomyFieldWithTwoActivity() {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track->taxonomyActivities()->attach($activity->id);
        $activity1 = TaxonomyActivity::factory()->create();
        $track->taxonomyActivities()->attach($activity1->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $json = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue(in_array('activity_'.$activity->id,$json['taxonomy']['activity']));
        $this->assertTrue(in_array('activity_'.$activity1->id,$json['taxonomy']['activity']));

    }
    public function testTaxonomyFieldWithTheme() {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $theme = TaxonomyTheme::factory()->create();
        $track->taxonomyThemes()->attach($theme->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $json = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('theme_'.$theme->id,$json['taxonomy']['theme'][0]);

    }
    public function testTaxonomyFieldWithAllTaxonomies() {
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

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $json = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('activity_'.$activity->id,$json['taxonomy']['activity'][0]);
        $this->assertEquals('theme_'.$theme->id,$json['taxonomy']['theme'][0]);
        $this->assertEquals('who_'.$who->id,$json['taxonomy']['who'][0]);
        $this->assertEquals('when_'.$when->id,$json['taxonomy']['when'][0]);
        $this->assertEquals('where_'.$where->id,$json['taxonomy']['where'][0]);
    }

    public function testFeatureImageWithImage() {

        $media = EcMedia::factory()->create();
        $api_url = route('api.ec.media.geojson',['id'=>$media->id],true);

        $ecTrack = EcTrack::factory()->create();
        $ecTrack->featureImage()->associate($media->id);
        $ecTrack->save();

        $app = App::factory()->create();
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.json', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('image',$json);
        $this->assertIsArray($json['image']);
        $image=$json['image'];

        $this->assertArrayHasKey('id',$image);
        $this->assertArrayHasKey('url',$image);
        $this->assertArrayHasKey('api_url',$image);
        $this->assertArrayHasKey('caption',$image);
        $this->assertArrayHasKey('sizes',$image);

        $this->assertEquals($media->id,$image['id']);
        $this->assertEquals($media->description,$image['caption']);
        $this->assertEquals($media->url,$image['url']);
        $this->assertEquals($api_url,$image['api_url']);

        // SIZES
        $this->assertIsArray($image['sizes']);
        $this->assertCount(4,$image['sizes']);

        $this->assertArrayHasKey('108x137',$image['sizes']);
        $this->assertArrayHasKey('108x148',$image['sizes']);
        $this->assertArrayHasKey('100x200',$image['sizes']);
        $this->assertArrayHasKey('original',$image['sizes']);

    }

    public function testFeatureImageWithoutImage() {
        $ecTrack = EcTrack::factory()->create();

        $app = App::factory()->create();
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.json', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertIsArray($json);

        $this->assertArrayNotHasKey('image',$json);

    }



}
