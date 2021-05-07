<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\UgcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TrackTest extends TestCase
{
    use RefreshDatabase;

    public function testGetGeoJson()
    {
        $ugcTrack = UgcTrack::factory()->create();
        $response = $this->get(route("api.ugc.track.geojson", ['id' => $ugcTrack->id]));
        $this->assertSame(200, $response->status());
        $json = $response->json();
        $this->assertArrayHasKey('type', $json);
        $this->assertSame('Feature', $json["type"]);
    }

    public function testGetGeoJsonMissingId()
    {
        $response = $this->get(route("api.ugc.track.geojson", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }
}
