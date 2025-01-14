<?php

namespace Tests\Unit\EcMedia;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EcTrackGetNearMediaTest extends TestCase
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

    public function test_no_get_near_ec_media()
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
        $media11 = EcMedia::factory()->create([
            'name' => 'TestMedia1',
            'geometry' => DB::raw('ST_MakePoint(10.0003, 46)'),
            'url' => '/ec_media_test/test.jpg',
        ]);
        $media12 = EcMedia::factory()->create([
            'name' => 'TestMedia2',
            'geometry' => DB::raw('ST_MakePoint(10.0005, 46)'),
            'url' => '/ec_media_test/test.jpg',
        ]);
        $poi = EcPoi::factory()->create([
            'name' => 'TestPoi',
            'geometry' => DB::raw("(ST_GeomFromText('POINT(10.0005 46)')"),
        ]);

        $geojson = $poi->getNeighbourEcMedia($poi->id);
        $this->assertNotNull($geojson);
        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('type', $geojson);
        $this->assertSame('FeatureCollection', $geojson['type']);
        $this->assertArrayHasKey('features', $geojson);
        $this->assertIsArray($geojson['features']);
    }
}
