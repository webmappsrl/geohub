<?php

namespace Tests\Unit\EcMedia;

use App\Models\EcMedia;
use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EcTrackGetNearMediaTest extends TestCase
{
    use RefreshDatabase;

    public function testNoGetNearEcMedia()
    {
        $track = EcTrack::factory()->create();

        $geojson = $track->getRelatedUgcGeojson();


        $this->assertNotNull($geojson);
        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('type', $geojson);
        $this->assertSame('FeatureCollection', $geojson['type']);
        $this->assertArrayHasKey('features', $geojson);
        $this->assertIsArray($geojson['features']);
        $this->assertCount(0, $geojson['features']);
    }

    public function _testGetNearEcMedia()
    {
        $track = EcTrack::factory()->create();

        EcMedia::factory([
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11 43)'))"),
        ])->create();

        $geojson = $track->getNearEcMedia($track->id);
        $this->assertNotNull($geojson);
        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('type', $geojson);
        $this->assertSame('FeatureCollection', $geojson['type']);
        $this->assertArrayHasKey('features', $geojson);
        $this->assertIsArray($geojson['features']);
    }
}