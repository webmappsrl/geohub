<?php

namespace Tests\Feature\Api\Ec\Track;

use App\Models\App;
use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NearestToLocationTest extends TestCase
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

    public function test_api_works_with_invalid_parameters()
    {
        App::factory([
            'app_id' => 'it.webmapp.webmapp',
        ])->create();
        $result = $this->getJson('/api/ec/track/nearest/test/test?app_id=it.webmapp.webmapp', []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('type', $json);
        $this->assertIsString($json['type']);
        $this->assertSame('FeatureCollection', $json['type']);
        $this->assertArrayHasKey('features', $json);
        $this->assertIsArray($json['features']);
        $this->assertCount(0, $json['features']);
    }

    public function test_api_works()
    {
        App::factory([
            'app_id' => 'it.webmapp.webmapp',
        ])->create();
        $result = $this->getJson('/api/ec/track/nearest/10/40?app_id=it.webmapp.webmapp', []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('type', $json);
        $this->assertIsString($json['type']);
        $this->assertSame('FeatureCollection', $json['type']);
        $this->assertArrayHasKey('features', $json);
        $this->assertIsArray($json['features']);
        $this->assertCount(0, $json['features']);
    }

    public function test_return_order_is_correct()
    {
        $ids = [];
        $geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [0, 0, 0],
                [0.01, 0, 0],
            ],
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')"),
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [0.01, 0, 0],
                [0.02, 0, 0],
            ],
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')"),
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [0.02, 0, 0],
                [0.03, 0, 0],
            ],
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')"),
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [0.03, 0, 0],
                [0.04, 0, 0],
            ],
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')"),
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [0.04, 0, 0],
                [0.05, 0, 0],
            ],
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')"),
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [0.05, 0, 0],
                [0.06, 0, 0],
            ],
        ]);
        EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')"),
        ])->count(5)->create();
        App::factory([
            'app_id' => 'it.webmapp.webmapp',
        ])->create();
        $result = $this->getJson('/api/ec/track/nearest/0/0?app_id=it.webmapp.webmapp', []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('type', $json);
        $this->assertIsString($json['type']);
        $this->assertSame('FeatureCollection', $json['type']);
        $this->assertArrayHasKey('features', $json);
        $this->assertIsArray($json['features']);
        $this->assertCount(5, $json['features']);
        foreach ($ids as $pos => $id) {
            $this->assertArrayHasKey($pos, $json['features']);
            $this->assertIsArray($json['features'][$pos]);
            $this->assertArrayHasKey('type', $json['features'][$pos]);
            $this->assertSame('Feature', $json['features'][$pos]['type']);
            $this->assertArrayHasKey('properties', $json['features'][$pos]);
            $this->assertArrayHasKey('id', $json['features'][$pos]['properties']);
            $this->assertSame($id, $json['features'][$pos]['properties']['id']);
            $this->assertArrayHasKey('geometry', $json['features'][$pos]);
        }
    }

    public function test_return_order_is_correct_with_only_two_tracks_in_range()
    {
        $ids = [];
        $geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [0, 0, 0],
                [0.01, 0, 0],
            ],
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')"),
        ])->create();
        $ids[] = $track->id;
        $geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [0.01, 0, 0],
                [0.02, 0, 0],
            ],
        ]);
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')"),
        ])->create();
        $geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [8, 0, 0],
                [9, 0, 0],
            ],
        ]);
        $ids[] = $track->id;
        EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')"),
        ])->count(5)->create();

        App::factory([
            'app_id' => 'it.webmapp.webmapp',
        ])->create();
        $result = $this->getJson('/api/ec/track/nearest/0/0?app_id=it.webmapp.webmapp', []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('type', $json);
        $this->assertIsString($json['type']);
        $this->assertSame('FeatureCollection', $json['type']);
        $this->assertArrayHasKey('features', $json);
        $this->assertIsArray($json['features']);
        $this->assertCount(2, $json['features']);
        foreach ($ids as $pos => $id) {
            $this->assertArrayHasKey($pos, $json['features']);
            $this->assertIsArray($json['features'][$pos]);
            $this->assertArrayHasKey('type', $json['features'][$pos]);
            $this->assertSame('Feature', $json['features'][$pos]['type']);
            $this->assertArrayHasKey('properties', $json['features'][$pos]);
            $this->assertArrayHasKey('id', $json['features'][$pos]['properties']);
            $this->assertSame($id, $json['features'][$pos]['properties']['id']);
            $this->assertArrayHasKey('geometry', $json['features'][$pos]);
        }
    }
}
