<?php

namespace Tests\Feature\Api\Ec;

use App\Http\Controllers\EditorialContentController;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\TaxonomyWhere;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EcPoiGetTest extends TestCase
{
    use RefreshDatabase;

    public function testNoIdReturnCode404()
    {
        $result = $this->getJson('/api/ec/poi/0', []);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testGeoJsonStructure()
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
}
