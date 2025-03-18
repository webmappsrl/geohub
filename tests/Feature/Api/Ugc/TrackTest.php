<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\TaxonomyWhere;
use App\Models\UgcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackTest extends TestCase
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

    public function test_get_geo_json()
    {
        $ugcTrack = UgcTrack::factory()->create();
        $response = $this->get(route('api.ugc.track.geojson', ['id' => $ugcTrack->id]));
        $this->assertSame(200, $response->status());
        $json = $response->json();
        $this->assertArrayHasKey('type', $json);
        $this->assertSame('Feature', $json['type']);
    }

    public function test_get_geo_json_missing_id()
    {
        $response = $this->get(route('api.ugc.track.geojson', ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function test_associate_zero_taxonomy_where_with_ugc_poi()
    {
        $track = UgcTrack::factory()->create();
        $response = $this->post(route('api.ugc.track.associate', ['id' => $track->id]));
        $this->assertSame(200, $response->status());

        $track = UgcTrack::find($track->id);
        $this->assertCount(0, $track->taxonomy_wheres);
    }

    public function test_associate_unknown_taxonomy_where_with_ugc_poi()
    {
        $track = UgcTrack::factory()->create();
        $response = $this->post(route('api.ugc.track.associate', [
            'id' => $track->id,
            'where_ids' => [12],
        ]));
        $this->assertSame(200, $response->status());

        $track = UgcTrack::find($track->id);
        $this->assertCount(0, $track->taxonomy_wheres);
    }

    public function test_associate_taxonomy_where_with_ugc_poi()
    {
        $track = UgcTrack::factory()->create();
        $wheres = TaxonomyWhere::factory()->count(2)->create();
        $whereIds = $wheres->pluck('id')->toArray();
        $response = $this->post(route('api.ugc.track.associate', [
            'id' => $track->id,
            'where_ids' => $whereIds,
        ]));
        $this->assertSame(200, $response->status());

        $track = UgcTrack::find($track->id);
        $this->assertCount(2, $track->taxonomy_wheres);
        $ids = $track->taxonomy_wheres->pluck('id')->toArray();
        $this->assertSame($whereIds, $ids);
    }
}
