<?php

namespace Tests\Feature\Api\Ec;

use App\Models\EcTrack;
use App\Models\TaxonomyWhere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EcTrackUpdateTest extends TestCase
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

    public function testUpdateEleMax()
    {
        $ecTrack = EcTrack::factory()->create(['ele_max' => 0]);
        $payload = [
            'ele_max' => 100,
        ];

        $result = $this->putJson('/api/ec/track/update/' . $ecTrack->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $ecTrackUpdated = EcTrack::find($ecTrack->id);

        $this->assertEquals(100, $ecTrackUpdated->ele_max);
    }

    public function testUpdateEleMin()
    {
        $ecTrack = EcTrack::factory()->create(['ele_min' => 0]);
        $payload = [
            'ele_min' => 100,
        ];

        $result = $this->putJson('/api/ec/track/update/' . $ecTrack->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $ecTrackUpdated = EcTrack::find($ecTrack->id);

        $this->assertEquals(100, $ecTrackUpdated->ele_min);
    }

    public function testUpdateAscent()
    {
        $ecTrack = EcTrack::factory()->create(['ascent' => 1]);
        $payload = [
            'ascent' => 100,
        ];

        $result = $this->putJson('/api/ec/track/update/' . $ecTrack->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $ecTrackUpdated = EcTrack::find($ecTrack->id);

        $this->assertEquals(100, $ecTrackUpdated->ascent);
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

    public function testUpdate3DGeometry()
    {
        $query = "ST_GeomFromGeoJSON('{\"type\":\"LineString\",\"coordinates\":[[1,2,0],[4,5,0],[7,8,0]]}')";
        // $query = "ST_GeomFromGeoJSON('{\"type\":\"LineString\",\"coordinates\":[[1,2],[4,5],[7,8]]}')";
        $track = EcTrack::factory()->create(['geometry' => DB::raw($query)]);

        $geojson = $track->getGeoJson();

        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('type', $geojson);
        $this->assertArrayHasKey('properties', $geojson);
        $this->assertArrayHasKey('geometry', $geojson);

        // Check geometry before update
        $geom = $geojson['geometry'];
        $this->assertIsArray($geom);
        $this->assertArrayHasKey('type', $geom);
        $this->assertArrayHasKey('coordinates', $geom);
        $this->assertEquals('LineString', $geom['type']);
        $coord = $geom['coordinates'];
        $this->assertCount(3, $coord);
        foreach ($coord as $point) {
            $this->assertCount(3, $point);
        }
        $this->assertEquals(1, $coord[0][0]);
        $this->assertEquals(2, $coord[0][1]);
        $this->assertEquals(0, $coord[0][2]);
        $this->assertEquals(4, $coord[1][0]);
        $this->assertEquals(5, $coord[1][1]);
        $this->assertEquals(0, $coord[1][2]);
        $this->assertEquals(7, $coord[2][0]);
        $this->assertEquals(8, $coord[2][1]);
        $this->assertEquals(0, $coord[2][2]);

        // Update geometry with 3D
        $payload = [
            'geometry' => json_decode('{"type":"LineString","coordinates":[[1,2,3],[4,5,6],[7,8,9]]}', true)
        ];
        $result = $this->putJson('/api/ec/track/update/' . $track->id, $payload);
        $this->assertEquals(200, $result->getStatusCode());

        $track_updated = EcTrack::find($track->id);

        // Check geometry after update
        $geojson = $track_updated->getGeoJson();

        $this->assertIsArray($geojson);
        $this->assertArrayHasKey('type', $geojson);
        $this->assertArrayHasKey('properties', $geojson);
        $this->assertArrayHasKey('geometry', $geojson);

        $geom = $geojson['geometry'];
        $this->assertIsArray($geom);
        $this->assertArrayHasKey('type', $geom);
        $this->assertArrayHasKey('coordinates', $geom);
        $this->assertEquals('LineString', $geom['type']);
        $coord = $geom['coordinates'];
        $this->assertCount(3, $coord);
        foreach ($coord as $point) {
            $this->assertCount(3, $point);
        }
        $this->assertEquals(1, $coord[0][0]);
        $this->assertEquals(2, $coord[0][1]);
        $this->assertEquals(3, $coord[0][2]);
        $this->assertEquals(4, $coord[1][0]);
        $this->assertEquals(5, $coord[1][1]);
        $this->assertEquals(6, $coord[1][2]);
        $this->assertEquals(7, $coord[2][0]);
        $this->assertEquals(8, $coord[2][1]);
        $this->assertEquals(9, $coord[2][2]);
    }
}
