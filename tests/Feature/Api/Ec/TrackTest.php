<?php

namespace Tests\Feature\Api\Ec;

use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\TaxonomyWhere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TrackTest extends TestCase
{
    use RefreshDatabase;

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
}
