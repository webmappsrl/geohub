<?php

namespace Tests\Feature\Api\Ec\Track;

use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NearestToLocationTest extends TestCase {
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
    public function check_api_works_with_invalid_parameters() {
        $result = $this->getJson('/api/ec/track/nearest/test/test', []);

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
    public function check_api_works() {
        $result = $this->getJson('/api/ec/track/nearest/10/40', []);

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
    public function check_return_order_is_correct() {
        $ids = [];
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [0, 0, 0],
                [1, 0, 0]
            ]
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [1, 0, 0],
                [2, 0, 0]
            ]
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [2, 0, 0],
                [3, 0, 0]
            ]
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [3, 0, 0],
                [4, 0, 0]
            ]
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [4, 0, 0],
                [5, 0, 0]
            ]
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [5, 0, 0],
                [6, 0, 0]
            ]
        ]);
        EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ])->count(5)->create();

        $result = $this->getJson('/api/ec/track/nearest/0/0', []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(5, $json["features"]);
        foreach ($ids as $pos => $id) {
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
