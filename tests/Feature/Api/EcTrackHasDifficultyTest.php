<?php

namespace Tests\Feature\Api;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcTrackHasDifficultyTest extends TestCase
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

    public function test_ec_track_download_geojson()
    {
        $json = $this->_getJsonTrack('api.ec.track.download.geojson');

        $this->assertArrayHasKey('properties', $json);
        $this->assertArrayHasKey('difficulty', $json['properties']);
        $this->assertEquals('alta', $json['properties']['difficulty']['it']);
        $this->assertEquals('high', $json['properties']['difficulty']['en']);
    }

    public function test_ec_track()
    {
        $json = $this->_getJsonTrack('api.ec.track.json');

        $this->assertArrayHasKey('properties', $json);
        $this->assertArrayHasKey('difficulty', $json['properties']);
        $this->assertEquals('alta', $json['properties']['difficulty']['it']);
        $this->assertEquals('high', $json['properties']['difficulty']['en']);
    }

    public function test_ec_track_geojson()
    {
        $json = $this->_getJsonTrack('api.ec.track.view.geojson');

        $this->assertArrayHasKey('properties', $json);
        $this->assertArrayHasKey('difficulty', $json['properties']);
        $this->assertEquals('alta', $json['properties']['difficulty']['it']);
        $this->assertEquals('high', $json['properties']['difficulty']['en']);
    }

    public function test_app_elbrus_geojson()
    {
        $json = $this->_getJsonTrack('api.app.elbrus.geojson.track');

        $this->assertArrayHasKey('properties', $json);
        $this->assertArrayHasKey('difficulty', $json['properties']);
        $this->assertEquals('alta', $json['properties']['difficulty']['it']);
        $this->assertEquals('high', $json['properties']['difficulty']['en']);
    }

    public function test_app_elbrus_json()
    {
        $json = $this->_getJsonTrack('api.app.elbrus.geojson.track.json');

        $this->assertArrayHasKey('difficulty', $json);
        $this->assertEquals('alta', $json['difficulty']['it']);
        $this->assertEquals('high', $json['difficulty']['en']);
    }

    public function test_app_elbrus_taxonomies()
    {
        // api/app/elbrus/{app_id}/taxonomies/track_{taxonomy_name}_{term_id}.json
        $difficulty = [
            'it' => 'alta',
            'en' => 'high',
        ];

        $user = User::factory()->create();
        $image = EcMedia::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track1 = EcTrack::factory()->create(['difficulty' => $difficulty]);
        $track1->user_id = $user->id;
        $track1->featureImage()->associate($image);
        $track1->ecMedia()->attach($image);
        $track1->save();
        $track1->taxonomyActivities()->attach([$activity->id]);

        $track2 = EcTrack::factory()->create(['difficulty' => $difficulty]);
        $track2->user_id = $user->id;
        $track2->featureImage()->associate($image);
        $track2->ecMedia()->attach($image);
        $track2->save();
        $track2->taxonomyActivities()->attach([$activity->id]);

        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();

        $uri = "api/app/elbrus/{$app->id}/taxonomies/track_activity_{$activity->id}.json";
        $result = $this->getJson($uri);
        $this->assertEquals(200, $result->getStatusCode());

        $tracks = json_decode($result->content(), true);
        $this->assertIsArray($tracks);

        $this->assertCount(2, $tracks);

        $fields = [
            'id', 'description', 'excerpt', 'source_id', 'import_method', 'source', 'distance', 'ascent', 'descent', 'difficulty',
            'ele:from', 'ele:to', 'ele:min', 'ele:max', 'duration:forward', 'duration:backward',
            'image', 'imageGallery',
        ];

        foreach ($fields as $field) {
            $this->assertArrayHasKey($field, $tracks[0]);
        }
        $this->assertEquals('alta', $tracks[0]['difficulty']['it']);
        $this->assertEquals('high', $tracks[0]['difficulty']['en']);
        $this->assertEquals('alta', $tracks[1]['difficulty']['it']);
        $this->assertEquals('high', $tracks[1]['difficulty']['en']);
    }

    public function _getJsonTrack($route_name)
    {
        $difficulty = [
            'it' => 'alta',
            'en' => 'high',
        ];
        $track = EcTrack::factory()->create(['difficulty' => $difficulty]);
        if (preg_match('/elbrus/', $route_name)) {
            $app = App::factory()->create();
            $result = $this->get(route($route_name, ['app_id' => $app->id, 'track_id' => $track->id]));
        } else {
            $result = $this->get(route($route_name, ['id' => $track->id]));
        }
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertJson($result->getContent());
        $json = json_decode($result->getContent(), true);

        return $json;
    }
}
