<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\TaxonomyWhere;
use App\Models\UgcMedia;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UgcMediaUpdateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function test_coordinates_updated_correctly()
    {
        $geometry = [
            "type" => "Point",
            "coordinates" => [10, 10]
        ];
        $media = UgcMedia::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('" . json_encode($geometry) . "')")
        ])->create();

        $geometry['coordinates'] = [20, 20];

        $geojson = [
            'type' => 'Feature',
            'properties' => [],
            'geometry' => $geometry
        ];

        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->times(0);
        });

        $response = $this->postJson(route("api.ugc.media.update", [$media->id]), [
            'geojson' => $geojson
        ]);
        $response->assertStatus(204);

        $media = UgcMedia::find($media->id);

        $geojson = $media->getGeojson();
        $this->assertTrue(isset($geojson['geometry']['type']));
        $this->assertEquals('Point', $geojson['geometry']['type']);
        $this->assertTrue(isset($geojson['geometry']['coordinates']));
        $this->assertIsArray($geojson['geometry']['coordinates']);
        $this->assertCount(2, $geojson['geometry']['coordinates']);
        $this->assertEquals(20, $geojson['geometry']['coordinates'][0]);
        $this->assertEquals(20, $geojson['geometry']['coordinates'][1]);
    }

    public function test_where_ids_updated_correctly()
    {
        $media = UgcMedia::factory()->create();
        $wheres = TaxonomyWhere::factory()->count(3)->create();

        $geojson = [
            'type' => 'Feature',
            'properties' => [
                'where_ids' => $wheres->pluck('id')->toArray()
            ],
        ];

        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->times(0);
        });

        $response = $this->postJson(route("api.ugc.media.update", [$media->id]), [
            'geojson' => $geojson
        ]);
        $response->assertStatus(204);

        $media = UgcMedia::find($media->id);
        $this->assertCount(3, $media->taxonomy_wheres);
    }
}
