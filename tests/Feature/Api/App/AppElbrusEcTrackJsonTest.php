<?php

namespace Tests\Feature\Api\App;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\TaxonomyWhere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppElbrusEcTrackJsonTest extends TestCase
{
    use RefreshDatabase;

    public function testNoAppAndNoTrackReturns404()
    {
        $result = $this->getJson('/api/app/elbrus/0/geojson/ec_track_0.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testAppAndNoTrackReturns404()
    {
        $app = App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_0.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testNoAppTrackReturns404()
    {
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/0/geojson/ec_track_' . $track->id . '.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testAppAndTrackReturns200()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testMappingUnderscoreAndColon()
    {
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
        $this->assertEquals($track->ascent, $json['ascent']);
    }

    public function testSpecialIdField()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $this->assertEquals(200, $result->getStatusCode());

        $json = json_decode($result->content(), true);
        $this->assertEquals('ec_track_' . $track->id, $json['id']);
    }

    public function testTaxonomyFieldWithActivity()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track->taxonomyActivities()->attach($activity->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $json = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('activity_' . $activity->id, $json['taxonomy']['activity'][0]);
    }

    public function testTaxonomyFieldWithTwoActivity()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track->taxonomyActivities()->attach($activity->id);
        $activity1 = TaxonomyActivity::factory()->create();
        $track->taxonomyActivities()->attach($activity1->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $json = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue(in_array('activity_' . $activity->id, $json['taxonomy']['activity']));
        $this->assertTrue(in_array('activity_' . $activity1->id, $json['taxonomy']['activity']));
    }

    public function testTaxonomyFieldWithTheme()
    {
        $app = App::factory()->create();
        $track = EcTrack::factory()->create();
        $theme = TaxonomyTheme::factory()->create();
        $track->taxonomyThemes()->attach($theme->id);

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $json = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('theme_' . $theme->id, $json['taxonomy']['theme'][0]);
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

        $result = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);
        $json = json_decode($result->content(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('activity_' . $activity->id, $json['taxonomy']['activity'][0]);
        $this->assertEquals('theme_' . $theme->id, $json['taxonomy']['theme'][0]);
        $this->assertEquals('who_' . $who->id, $json['taxonomy']['who'][0]);
        $this->assertEquals('when_' . $when->id, $json['taxonomy']['when'][0]);
        $this->assertEquals('where_' . $where->id, $json['taxonomy']['where'][0]);
    }

    public function testFeatureImageWithImage()
    {
        $media = EcMedia::factory()->create();
        $api_url = route('api.ec.media.geojson', ['id' => $media->id], true);

        $ecTrack = EcTrack::factory()->create();
        $ecTrack->featureImage()->associate($media);
        $ecTrack->save();

        $app = App::factory()->create();
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.json', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('image', $json);
        $this->assertIsArray($json['image']);
        $image = $json['image'];

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
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.json', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertIsArray($json);

        $this->assertArrayNotHasKey('image', $json);
    }

    public function testGalleryWithImage()
    {
        $media1 = EcMedia::factory()->create();
        $media2 = EcMedia::factory()->create();
        $media3 = EcMedia::factory()->create();
        $api_url1 = route('api.ec.media.geojson', ['id' => $media1], true);
        $api_url2 = route('api.ec.media.geojson', ['id' => $media2], true);
        $api_url3 = route('api.ec.media.geojson', ['id' => $media3], true);

        $ecTrack = EcTrack::factory()->create();
        $ecTrack->ecMedia()->attach($media1);
        $ecTrack->ecMedia()->attach($media2);
        $ecTrack->ecMedia()->attach($media3);
        $ecTrack->save();

        $app = App::factory()->create();
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.json', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('imageGallery', $json);
        $this->assertIsArray($json['imageGallery']);
        $gallery = $json['imageGallery'];

        $this->assertIsArray($gallery);
        $this->assertCount(3, $gallery);
        $this->assertArrayHasKey('id', $gallery[0]);
        $this->assertArrayHasKey('url', $gallery[0]);
        $this->assertArrayHasKey('api_url', $gallery[0]);
        $this->assertArrayHasKey('caption', $gallery[0]);
        $this->assertArrayHasKey('sizes', $gallery[0]);

        $this->assertArrayHasKey('id', $gallery[1]);
        $this->assertArrayHasKey('url', $gallery[1]);
        $this->assertArrayHasKey('api_url', $gallery[1]);
        $this->assertArrayHasKey('caption', $gallery[1]);
        $this->assertArrayHasKey('sizes', $gallery[1]);

        $this->assertArrayHasKey('id', $gallery[2]);
        $this->assertArrayHasKey('url', $gallery[2]);
        $this->assertArrayHasKey('api_url', $gallery[2]);
        $this->assertArrayHasKey('caption', $gallery[2]);
        $this->assertArrayHasKey('sizes', $gallery[2]);

        $this->assertEquals($media1->id, $gallery[0]['id']);
        $this->assertEquals($media1->description, $gallery[0]['caption']);
        $this->assertEquals($media1->url, $gallery[0]['url']);
        $this->assertEquals($api_url1, $gallery[0]['api_url']);

        $this->assertEquals($media2->id, $gallery[1]['id']);
        $this->assertEquals($media2->description, $gallery[1]['caption']);
        $this->assertEquals($media2->url, $gallery[1]['url']);
        $this->assertEquals($api_url2, $gallery[1]['api_url']);

        $this->assertEquals($media3->id, $gallery[2]['id']);
        $this->assertEquals($media3->description, $gallery[2]['caption']);
        $this->assertEquals($media3->url, $gallery[2]['url']);
        $this->assertEquals($api_url3, $gallery[2]['api_url']);

        // SIZES
        $this->assertIsArray($gallery[0]['sizes']);
        $this->assertCount(4, $gallery[0]['sizes']);
        $this->assertArrayHasKey('108x137', $gallery[0]['sizes']);
        $this->assertArrayHasKey('108x148', $gallery[0]['sizes']);
        $this->assertArrayHasKey('100x200', $gallery[0]['sizes']);
        $this->assertArrayHasKey('original', $gallery[0]['sizes']);

        $this->assertIsArray($gallery[1]['sizes']);
        $this->assertCount(4, $gallery[1]['sizes']);
        $this->assertArrayHasKey('108x137', $gallery[1]['sizes']);
        $this->assertArrayHasKey('108x148', $gallery[1]['sizes']);
        $this->assertArrayHasKey('100x200', $gallery[1]['sizes']);
        $this->assertArrayHasKey('original', $gallery[1]['sizes']);

        $this->assertIsArray($gallery[2]['sizes']);
        $this->assertCount(4, $gallery[2]['sizes']);
        $this->assertArrayHasKey('108x137', $gallery[2]['sizes']);
        $this->assertArrayHasKey('108x148', $gallery[2]['sizes']);
        $this->assertArrayHasKey('100x200', $gallery[2]['sizes']);
        $this->assertArrayHasKey('original', $gallery[2]['sizes']);
    }

    public function testGalleryWithoutImage()
    {
        $ecTrack = EcTrack::factory()->create();

        $app = App::factory()->create();
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.json', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertIsArray($json);

        $this->assertArrayNotHasKey('imageGallery', $json);
    }

    public function testGpxField()
    {
        $ecTrack = EcTrack::factory()->create();

        $app = App::factory()->create();
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.json', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertIsArray($json);

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
        $response = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $ecTrack->id . '.json', []);

        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertIsArray($json);

        $this->assertArrayHasKey('kml_url', $json);
        $this->assertIsString($json['kml_url']);
        $this->assertStringContainsString('http', $json['kml_url']);
        $this->assertStringContainsString($ecTrack->id, $json['kml_url']);
        $this->assertStringContainsString('download', $json['kml_url']);
        $this->assertStringContainsString('.kml', $json['kml_url']);
    }
}
