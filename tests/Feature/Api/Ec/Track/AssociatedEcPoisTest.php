<?php

namespace Tests\Feature\Api\Ec\Track;

use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssociatedEcPoisTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function test_empty_result() {
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/ec/track/' . $track->id . "/associated_ec_pois", []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(0, $json["features"]);
    }

    public function test_result_with_some_pois() {
        $track = EcTrack::factory()->create();
        $pois = EcPoi::factory(10)->create();

        $track->ecPois()->sync($pois);
        $result = $this->getJson('/api/ec/track/' . $track->id . "/associated_ec_pois", []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(10, $json["features"]);
    }
}
