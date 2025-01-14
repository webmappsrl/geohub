<?php

namespace Tests\Feature\Api\Ec;

use App\Models\App;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class EcTrack3DTest
 * Test API translated content
 * api/ec/track/{id}
 * api/ec/track/{id}.geojson
 * api/ec/track/download/{id}
 * api/ec/track/download/{id}.geojson
 * api/app/elbrus/{app_id}/geojson/ec_track_{track_id}.geojson
 */
class EcTrack3DTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    private function _check3D($geojson)
    {
        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('geometry', $geojson);
        $this->assertArrayHasKey('coordinates', $geojson['geometry']);
        $coords = $geojson['geometry']['coordinates'];
        $this->assertIsArray($coords);
        $this->assertTrue(count($coords) > 0);
        foreach ($coords as $point) {
            $this->assertCount(3, $point);
        }
    }

    /**
     * api/ec/track/{id}
     */
    public function test_api_ec_track()
    {
        $track = \App\Models\EcTrack::factory()->create();
        $response = $this->get('api/ec/track/'.$track->id);
        $this->assertEquals(200, $response->getStatusCode());
        $geojson = json_decode($response->getContent(), true);
        $this->_check3D($geojson);
    }

    /**
     * api/ec/track/{id}.geojson
     */
    public function test_api_ec_track_geojson()
    {
        $track = \App\Models\EcTrack::factory()->create();
        $response = $this->getJson('api/ec/track/'.$track->id.'.geojson');
        $this->assertEquals(200, $response->getStatusCode());
        $geojson = json_decode($response->getContent(), true);
        $this->_check3D($geojson);
    }

    /**
     * api/ec/track/download/{id}
     */
    public function test_api_ec_track_download()
    {
        $track = \App\Models\EcTrack::factory()->create();
        $response = $this->getJson('api/ec/track/download/'.$track->id);
        $this->assertEquals(200, $response->getStatusCode());
        $geojson = json_decode($response->getContent(), true);
        $this->_check3D($geojson);
    }

    /**
     * api/ec/track/download/{id}.geojson
     */
    public function test_api_ec_track_download_geojson()
    {
        $track = \App\Models\EcTrack::factory()->create();
        $response = $this->getJson('api/ec/track/download/'.$track->id.'.geojson');
        $this->assertEquals(200, $response->getStatusCode());
        $geojson = json_decode($response->getContent(), true);
        $this->_check3D($geojson);
    }

    /**
     * api/app/elbrus/{app_id}/geojson/ec_track_{track_id}.geojson
     */
    public function test_api_app_elbrus_ec_track_geojson()
    {
        $app = App::factory()->create();
        $track = \App\Models\EcTrack::factory()->create();
        $response = $this->getJson('/api/app/elbrus/'.$app->id.'/geojson/ec_track_'.$track->id.'.geojson', []);

        $this->assertEquals(200, $response->getStatusCode());
        $geojson = json_decode($response->getContent(), true);
        $this->_check3D($geojson);
    }
}
