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

class EcTrackHasDurationBackwardTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function testEcTrackDownloadGeojson() {
        $json = $this->_getJsonTrack('api.ec.track.download.geojson');

        $this->assertArrayHasKey('properties', $json);
        $this->assertArrayHasKey('duration_backward', $json['properties']);
        $this->assertEquals(60, $json['properties']['duration_backward']);
    }

    public function testEcTrack() {
        $json = $this->_getJsonTrack('api.ec.track.json');

        $this->assertArrayHasKey('properties', $json);
        $this->assertArrayHasKey('duration_backward', $json['properties']);
        $this->assertEquals(60, $json['properties']['duration_backward']);
    }

    public function testEcTrackGeojson() {
        $json = $this->_getJsonTrack('api.ec.track.view.geojson');

        $this->assertArrayHasKey('properties', $json);
        $this->assertArrayHasKey('duration_backward', $json['properties']);
        $this->assertEquals(60, $json['properties']['duration_backward']);
    }

    public function testAppElbrusGeojson() {
        $json = $this->_getJsonTrack('api.app.elbrus.geojson.track');

        $this->assertArrayHasKey('properties', $json);
        $this->assertArrayHasKey('duration:backward', $json['properties']);
        $this->assertEquals(60, $json['properties']['duration:backward']);
    }

    public function testAppElbrusJson() {
        $json = $this->_getJsonTrack('api.app.elbrus.geojson.track.json');

        $this->assertArrayHasKey('duration:backward', $json);
        $this->assertEquals(60, $json['duration:backward']);
    }

    public function testAppElbrusTaxonomies() {
        // api/app/elbrus/{app_id}/taxonomies/track_{taxonomy_name}_{term_id}.json

        $user = User::factory()->create();
        $image = EcMedia::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track1 = EcTrack::factory()->create(['duration_backward' => 60]);
        $track1->user_id = $user->id;
        $track1->featureImage()->associate($image);
        $track1->ecMedia()->attach($image);
        $track1->save();
        $track1->taxonomyActivities()->attach([$activity->id]);

        $track2 = EcTrack::factory()->create(['duration_backward' => 60]);
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
            'image', 'imageGallery'
        ];

        foreach ($fields as $field) {
            $this->assertArrayHasKey($field, $tracks[0]);
        }
        $this->assertEquals(60, $tracks[0]['duration:backward']);
        $this->assertEquals(60, $tracks[1]['duration:backward']);
    }

    public function _getJsonTrack($route_name) {
        $track = EcTrack::factory()->create(['duration_backward' => 60]);
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
