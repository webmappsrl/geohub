<?php

namespace Tests\Feature\Api\Ec\Poi;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NeighbourMediaTest extends TestCase {
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
        $poi = EcPoi::factory()->create();
        $result = $this->getJson('/api/ec/poi/' . $poi->id . "/neighbour_media", []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        // todo: add new assertion or remove it $this->assertCount(0, $json["features"]);
    }

    public function test_result_with_some_neighbour_media() {
        $poi = EcPoi::factory([
            'geometry' => DB::raw("ST_GeomFromText('POINT(0 0)')")
        ])->create();
        $neighbourMedia = EcMedia::factory([
            'geometry' => DB::raw("ST_GeomFromText('POINT(0 0)')")
        ])->count(2)->create();
        $media = EcMedia::factory([
            'geometry' => DB::raw("ST_GeomFromText('POINT(5 5)')")
        ])->count(2)->create();

        Config::set('geohub.ec_poi_media_distance', 100);

        $result = $this->getJson('/api/ec/poi/' . $poi->id . "/neighbour_media", []);

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
            $this->assertTrue(in_array($feature['properties']['id'], $neighbourMedia->pluck('id')->toArray()));
        }
    }
}
