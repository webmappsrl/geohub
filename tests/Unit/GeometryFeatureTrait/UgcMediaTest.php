<?php

namespace Tests\Unit;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UgcMediaTest extends TestCase {
    use RefreshDatabase;

    public function testGetGeojsonWithoutGeometry() {
        $media = UgcMedia::factory([
            'geometry' => null
        ])->create();

        $geojson = $media->getGeojson();

        $this->assertNull($geojson);
    }

    public function testGetGeojsonWithGeometry() {
        $media = UgcMedia::factory([
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11 43)'))")
        ])->create();

        $geojson = $media->getGeojson();

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

    public function testGetRelatedUgcWithNoRelated() {
        $media = UgcMedia::factory([
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11 43)'))")
        ])->create();

        $geojson = $media->getRelatedUgcGeojson();

        $this->assertNotNull($geojson);
        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('type', $geojson);
        $this->assertSame('FeatureCollection', $geojson['type']);
        $this->assertArrayHasKey('features', $geojson);
        $this->assertIsArray($geojson['features']);
        $this->assertCount(0, $geojson['features']);
    }

    public function testGetRelatedUgcWithRelated() {
        $user = User::factory(1)->create()->first();
        $media = UgcMedia::factory([
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11 43)'))"),
            'user_id' => $user['id'],
            'created_at' => now()
        ])->create();

        UgcPoi::factory([
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11 43)'))"),
            'user_id' => $user['id'],
            'created_at' => now()
        ])->create();

        UgcTrack::factory([
            'geometry' => DB::raw("(ST_GeomFromText('LINESTRING(11 43, 12 43, 12 44, 11 44)'))"),
            'user_id' => $user['id'],
            'created_at' => now()
        ])->create();

        $geojson = $media->getRelatedUgcGeojson();

        $this->assertNotNull($geojson);
        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('type', $geojson);
        $this->assertSame('FeatureCollection', $geojson['type']);
        $this->assertArrayHasKey('features', $geojson);
        $this->assertIsArray($geojson['features']);
        $this->assertCount(2, $geojson['features']);
    }
}
