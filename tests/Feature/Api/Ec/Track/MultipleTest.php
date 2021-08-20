<?php

namespace Tests\Feature\Api\Ec\Track;

use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    /**
     * @test
     */
    public function check_api_works_with_no_ids() {
        $result = $this->getJson('/api/ec/track/multiple', []);

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

    /**
     * @test
     */
    public function check_api_works_with_unknown_ids() {
        $result = $this->getJson('/api/ec/track/multiple?ids=100,150', []);

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

    /**
     * @test
     */
    public function check_api_works_with_one_known_id() {
        $track = EcTrack::factory()->create();
        $id = $track->id;
        $result = $this->getJson("/api/ec/track/multiple?ids=$id", []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(1, $json["features"]);

        $this->assertArrayHasKey(0, $json["features"]);
        $this->assertIsArray($json["features"][0]);
        $this->assertArrayHasKey("type", $json["features"][0]);
        $this->assertSame("Feature", $json["features"][0]["type"]);
        $this->assertArrayHasKey("properties", $json["features"][0]);
        $this->assertArrayHasKey("id", $json["features"][0]["properties"]);
        $this->assertSame($id, $json["features"][0]["properties"]["id"]);
        $this->assertArrayHasKey("geometry", $json["features"][0]);
    }

    /**
     * @test
     */
    public function check_api_works_with_multiple_ids_limiting_to_three_results() {
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $track = EcTrack::factory()->create();
            $ids[] = $track->id;
            $i++;
        }
        $result = $this->getJson("/api/ec/track/multiple?ids=" . implode(',', $ids), []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(3, $json["features"]);

        for ($pos = 0; $pos < 3; $pos++) {
            $id = $ids[$pos];
            $this->assertArrayHasKey($pos, $json["features"]);
            $this->assertIsArray($json["features"][$pos]);
            $this->assertArrayHasKey("type", $json["features"][$pos]);
            $this->assertSame("Feature", $json["features"][$pos]["type"]);
            $this->assertArrayHasKey("properties", $json["features"][$pos]);
            $this->assertArrayHasKey("id", $json["features"][$pos]["properties"]);
            $this->assertSame($id, $json["features"][$pos]["properties"]["id"]);
            $this->assertArrayHasKey("geometry", $json["features"][$pos]);
        }
    }
}
