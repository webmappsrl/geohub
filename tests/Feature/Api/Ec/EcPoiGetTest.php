<?php

namespace Tests\Feature\Api\Ec;

use App\Models\EcPoi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EcPoiGetTest extends TestCase
{
    use RefreshDatabase;

    public function testNoIdReturnCode404()
    {
        $result = $this->getJson('/api/ec/poi/0', []);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testJsonStructure()
    {
        $data['geometry'] = DB::raw("(ST_GeomFromText('POINT(10.1 43.1)'))");
        $ecPoi = EcPoi::factory()->create($data);
        $result = $this->getJson('/api/ec/poi/' . $ecPoi->id, []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertEquals("Feature", $json->type);
        $this->assertIsObject($json->properties);

        $this->assertEquals($ecPoi->name, $json->properties->name);
        $this->assertEquals($ecPoi->excerpt, $json->properties->excerpt);
        $this->assertEquals($ecPoi->description, $json->properties->description);

        $this->assertIsObject($json->geometry);
        $this->assertEquals("Point", $json->geometry->type);
        $this->assertEquals(10.1, $json->geometry->coordinates[0]);
        $this->assertEquals(43.1, $json->geometry->coordinates[1]);
    }

    public function testDownloadGeoJsonData()
    {
        $data['geometry'] = DB::raw("(ST_GeomFromText('POINT(10.43 43.10)'))");
        $ecPoi = EcPoi::factory()->create($data);
        $this->assertIsObject($ecPoi);

        $result = $this->getJson('/api/ec/poi/download/' . $ecPoi->id, []);
        $this->assertEquals(200, $result->getStatusCode());
        
        $json = json_decode($result->getContent());

        $this->assertEquals("Feature", $json->type);
        $this->assertIsObject($json->properties);

        $this->assertEquals($ecPoi->name, $json->properties->name);
        $this->assertEquals($ecPoi->excerpt, $json->properties->excerpt);
        $this->assertEquals($ecPoi->description, $json->properties->description);

        $this->assertIsObject($json->geometry);
        $this->assertEquals("Point", $json->geometry->type);
        $this->assertEquals(10.43, $json->geometry->coordinates[0]);
        $this->assertEquals(43.10, $json->geometry->coordinates[1]);
        // $this->assertEquals("geojson", $json->type);
    }

    public function testDownloadKmlData()
    {
        $data['name'] = 'Test point';
        $data['description'] = 'Test point description.';
        $data['geometry'] = DB::raw("(ST_GeomFromText('POINT(10.43 43.10)'))");
        $kml = <<<KML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<Placemark><name>Test point</name><description>Test point description.</description><Point><coordinates>10.43,43.1</coordinates></Point></Placemark>
</kml>
KML;
        $ecPoi = EcPoi::factory()->create($data);
        $this->assertIsObject($ecPoi);

        $result = $this->getJson('/api/ec/poi/download/' . $ecPoi->id . '/kml', []);
        $this->assertEquals(200, $result->getStatusCode());
        
        $kmlResponse = $result->getContent();

        $this->assertEquals($kmlResponse, $kml);
        
        // $this->assertEquals("geojson", $json->type);
    }
}
