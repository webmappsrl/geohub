<?php

namespace Tests\Unit\GeometryFeatureTrait;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UgcPoiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_get_geojson_without_geometry()
    {
        $poi = UgcPoi::factory([
            'geometry' => null,
        ])->create();

        $geojson = $poi->getGeojson();

        $this->assertNull($geojson);
    }

    public function test_get_geojson_with_geometry()
    {
        $poi = UgcPoi::factory([
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11 43)'))"),
        ])->create();

        $geojson = $poi->getGeojson();

        $this->assertNotNull($geojson);
        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('type', $geojson);
        $this->assertSame('Feature', $geojson['type']);
        $this->assertArrayHasKey('properties', $geojson);
        $this->assertIsArray($geojson['properties']);
        $this->assertArrayHasKey('id', $geojson['properties']);
        $this->assertArrayHasKey('geometry', $geojson);
        $this->assertIsArray($geojson['geometry']);
        $this->assertArrayHasKey('type', $geojson['geometry']);
        $this->assertSame('Point', $geojson['geometry']['type']);
        $this->assertArrayHasKey('coordinates', $geojson['geometry']);
        $this->assertSame(json_encode([11, 43]), json_encode($geojson['geometry']['coordinates']));
    }

    public function test_get_related_ugc_with_no_related()
    {
        $poi = UgcPoi::factory([
            'name' => $this->faker->name(),
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11 43)'))"),
        ])->create();

        $geojson = $poi->getRelatedUgcGeojson();

        $this->assertNotNull($geojson);
        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('type', $geojson);
        $this->assertSame('FeatureCollection', $geojson['type']);
        $this->assertArrayHasKey('features', $geojson);
        $this->assertIsArray($geojson['features']);
        $this->assertCount(0, $geojson['features']);
    }

    public function test_get_related_ugc_with_related()
    {
        $user = User::factory(1)->create()->first();
        $poi = UgcPoi::factory([
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11 43)'))"),
            'user_id' => $user['id'],
            'created_at' => now(),
        ])->create();

        UgcMedia::factory([
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11 43)'))"),
            'user_id' => $user['id'],
            'created_at' => now(),
        ])->create();

        UgcTrack::factory([
            'geometry' => DB::raw("(ST_GeomFromText('LINESTRING(11 43, 12 43, 12 44, 11 44)'))"),
            'user_id' => $user['id'],
            'created_at' => now(),
        ])->create();

        $geojson = $poi->getRelatedUgcGeojson();

        $this->assertNotNull($geojson);
        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('type', $geojson);
        $this->assertSame('FeatureCollection', $geojson['type']);
        $this->assertArrayHasKey('features', $geojson);
        $this->assertIsArray($geojson['features']);
        $this->assertCount(2, $geojson['features']);
    }
}
