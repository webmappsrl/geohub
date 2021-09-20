<?php

namespace Tests\Feature\Api\Ec\Track;

use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NeighbourPoisTest extends TestCase {
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
        $result = $this->getJson('/api/ec/track/' . $track->id . "/neighbour_pois", []);

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

    public function test_result_with_some_neighbour_media() {
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromText('LINESTRING(0 0 0, 1 1 0)')")
        ])->create();
        $neighbourPois = EcPoi::factory([
            'geometry' => DB::raw("ST_GeomFromText('POINT(0 0)')")
        ])->count(2)->create();
        EcPoi::factory([
            'geometry' => DB::raw("ST_GeomFromText('POINT(5 5)')")
        ])->count(2)->create();

        $result = $this->getJson('/api/ec/track/' . $track->id . "/neighbour_pois", []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(2, $json["features"]);

        foreach ($json['features'] as $feature) {
            $this->assertIsArray($feature);
            $this->assertArrayHasKey('type', $feature);
            $this->assertArrayHasKey('geometry', $feature);
            $this->assertArrayHasKey('properties', $feature);
            $this->assertIsArray($feature['properties']);
            $this->assertArrayHasKey('id', $feature['properties']);
            $this->assertTrue(in_array($feature['properties']['id'], $neighbourPois->pluck('id')->toArray()));
        }
    }
}
