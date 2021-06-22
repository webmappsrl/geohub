<?php

namespace Tests\Feature\Api\Ec;

use App\Models\EcTrack;
use App\Models\TaxonomyWhere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $data['name'] = 'Test track';
        $data['description'] = 'Test track description.';
        $data['geometry'] = DB::raw("(ST_GeomFromText('LINESTRING(11 43,12 43,12 44,11 44)'))");
        $gpx = <<<KML
<?xml version="1.0"?>
<gpx version="1.1" creator="GDAL 2.2.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ogr="http://osgeo.org/gdal" xmlns="http://www.topografix.com/GPX/1/1" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">
<trk><trkseg><trkpt lon="11" lat="43"></trkpt><trkpt lon="12" lat="43"></trkpt><trkpt lon="12" lat="44"></trkpt><trkpt lon="11" lat="44"></trkpt></trkseg></trk>
</gpx>
KML;

        $ecTrack = EcTrack::factory()->create($data);
        $result = $this->getJson('/api/ec/track/download/gpx/' . $ecTrack->id, []);
        $this->assertEquals(200, $result->getStatusCode());
        
        $gpxResponse = $result->getContent();

        $this->assertEquals($gpxResponse, $gpx);
    }

    public function testDownloadKmlData()
    {
        $data['name'] = 'Test track';
        $data['description'] = 'Test track description.';
        $data['geometry'] = DB::raw("(ST_GeomFromText('LINESTRING(11 43,12 43,12 44,11 44)'))");
        $kml = <<<KML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<Placemark><name>Test track</name><description>Test track description.</description><LineString><coordinates>11,43 12,43 12,44 11,44</coordinates></LineString></Placemark>
</kml>
KML;
        $ecTrack = EcTrack::factory()->create($data);
        $result = $this->getJson('/api/ec/track/download/kml/' . $ecTrack->id, []);
        $this->assertEquals(200, $result->getStatusCode());
        
        $kmlResponse = $result->getContent();

        $this->assertEquals($kmlResponse, $kml);
    }
}
