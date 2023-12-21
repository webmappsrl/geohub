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

/**
 * | api/ec/track/download/{id}                                              | api.ec.track.download.geojson        |
 * | api/ec/track/download/{id}.geojson                                      | api.ec.track.download.geojson        |
 * | api/ec/track/{id}                                                       | api.ec.track.json                    |
 * | api/ec/track/{id}.geojson                                               | api.ec.track.view.geojson            |
 *
 * | api/app/elbrus/{app_id}/geojson/ec_track_{track_id}.geojson             | api.app.elbrus.geojson.track      |
 * | api/app/elbrus/{app_id}/geojson/ec_track_{track_id}.json                | api.app.elbrus.geojson.track.json |
 * | api/app/elbrus/{app_id}/taxonomies/track_{taxonomy_name}_{term_id}.json | api.app.elbrus.track.taxonomies      |
 */
class EcTrackEleInfoGetApiTest extends TestCase
{
    use RefreshDatabase;

    private array $fields = [
        'distance' => 100,
        'ascent' => 100,
        'descent' => 100,
        'ele_min' => 100,
        'ele_max' => 100,
        'ele_from' => 100,
        'ele_to' => 100,
        'duration_forward' => 100,
        'duration_backward' => 100,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function testEcTrackDownloadGeojson()
    {
        $json = $this->_getJsonTrack('api.ec.track.download.geojson');

        $this->assertArrayHasKey('properties', $json);
        foreach ($this->fields as $key => $val) {
            $this->assertArrayHasKey($key, $json['properties']);
            $this->assertEquals($val, $json['properties'][$key]);
        }
    }

    public function testEcTrack()
    {
        $json = $this->_getJsonTrack('api.ec.track.json');

        $this->assertArrayHasKey('properties', $json);
        foreach ($this->fields as $key => $val) {
            $this->assertArrayHasKey($key, $json['properties']);
            $this->assertEquals($val, $json['properties'][$key]);
        }
    }

    public function testEcTrackGeojson()
    {
        $json = $this->_getJsonTrack('api.ec.track.view.geojson');

        $this->assertArrayHasKey('properties', $json);
        foreach ($this->fields as $key => $val) {
            $this->assertArrayHasKey($key, $json['properties']);
            $this->assertEquals($val, $json['properties'][$key]);
        }
    }

    public function testAppElbrusGeojson()
    {
        $json = $this->_getJsonTrack('api.app.elbrus.geojson.track');

        $this->assertArrayHasKey('properties', $json);
        foreach ($this->fields as $key => $val) {
            $fieldWithColon = str_replace('_', ':', $key);
            $this->assertArrayHasKey($fieldWithColon, $json['properties']);
            $this->assertEquals($val, $json['properties'][$fieldWithColon]);
        }
    }

    public function testAppElbrusJson()
    {
        $json = $this->_getJsonTrack('api.app.elbrus.geojson.track.json');

        foreach ($this->fields as $key => $val) {
            $fieldWithColon = str_replace('_', ':', $key);
            $this->assertArrayHasKey($fieldWithColon, $json);
            $this->assertEquals($val, $json[$fieldWithColon]);
        }
    }

    public function testAppElbrusTaxonomies()
    {
        // api/app/elbrus/{app_id}/taxonomies/track_{taxonomy_name}_{term_id}.json
        $user = User::factory()->create();
        $image = EcMedia::factory()->create();
        $activity = TaxonomyActivity::factory()->create();

        $track1 = EcTrack::factory()->create($this->fields);
        $track1->user_id = $user->id;
        $track1->featureImage()->associate($image);
        $track1->ecMedia()->attach($image);
        $track1->save();
        $track1->taxonomyActivities()->attach([$activity->id]);

        $track2 = EcTrack::factory()->create($this->fields);
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

        foreach ($this->fields as $key => $val) {
            $elbrus_key = str_replace('_', ':', $key);
            $this->assertArrayHasKey($elbrus_key, $tracks[0]);
            $this->assertArrayHasKey($elbrus_key, $tracks[1]);
            $this->assertEquals($val, $tracks[0][$elbrus_key]);
            $this->assertEquals($val, $tracks[1][$elbrus_key]);
        }
    }

    public function _getJsonTrack($route_name)
    {
        $track = EcTrack::factory()->create($this->fields);

        if (preg_match('/elbrus/', $route_name)) {
            $app = App::factory()->create();
            $result = $this->get(route($route_name, ['app_id' => $app->id, 'track_id' => $track->id]));
        } else {
            $result = $this->get(route($route_name, ['id' => $track->id]));
        }

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertJson($result->getContent());

        return json_decode($result->getContent(), true);
    }
}
