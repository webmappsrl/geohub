<?php

namespace Tests\Feature\Api\Ec;

use App\Models\EcTrack;
use App\Models\TaxonomyWhere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackTest extends TestCase
{
    use RefreshDatabase;

    public function testGetGeoJson()
    {
        $ecTrack = EcTrack::factory()->create();
        $response = $this->get(route("api.ec.track.geojson", ['id' => $ecTrack->id]));
        $this->assertSame(200, $response->status());
        $json = $response->json();
        $this->assertArrayHasKey('type', $json);
        $this->assertSame('Feature', $json["type"]);
    }

    public function testGetGeoJsonMissingId()
    {
        $response = $this->get(route("api.ec.track.geojson", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testNoIdReturnCode404()
    {
        $result = $this->putJson('/api/ec/track/update/0', []);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testSendDistanceCompUpdateFieldDistanceComp()
    {
        $ecTrack = EcTrack::factory()->create();
        $newDistance = 123;
        $payload = [
            'distance_comp' => $newDistance,
        ];

        $result = $this->putJson('/api/ec/track/update/' . $ecTrack->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $ecTrackUpdated = EcTrack::find($ecTrack->id);

        $this->assertEquals($newDistance, $ecTrackUpdated->distance_comp);
    }

    public function testSendWheresIdsUpdateWhereRelation()
    {
        $ecTrack = EcTrack::factory()->create();
        $where = TaxonomyWhere::factory()->create();

        $payload = [
            'where_ids' => [$where->id],
        ];
        $result = $this->putJson('/api/ec/track/update/' . $ecTrack->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());

        $where = TaxonomyWhere::find($where->id);
        $tracks = $where->ecTrack;
        $this->assertCount(1, $tracks);
        $this->assertSame($ecTrack->id, $tracks->first()->id);
    }

    public function testDownloadGpxData()
    {
        $ecTrack = EcTrack::factory()->create();
        $result = $this->getJson('/api/ec/track/download/' . $ecTrack->id . '/gpx', []);
        $this->assertEquals(200, $result->getStatusCode());
    }
}
