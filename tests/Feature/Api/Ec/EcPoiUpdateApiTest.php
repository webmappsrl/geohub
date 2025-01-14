<?php

namespace Tests\Feature\Api\Ec;

use App\Models\EcPoi;
use App\Models\TaxonomyWhere;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EcPoiUpdateApiTest extends TestCase
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

    public function test_no_id_return_code404()
    {
        $result = $this->putJson('/api/ec/poi/update/0', []);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function test_send_coordinates_update_field_geometry()
    {
        $geometry = DB::raw("(ST_GeomFromText('POINT(10.43 43.70)'))");
        $ecPoi = EcPoi::factory()->create([
            'geometry' => $geometry,
        ]);
        $newGeometry = [
            'type' => 'Point',
            'coordinates' => [10.41, 43.75],
        ];

        $payload = [
            'geometry' => $newGeometry,
        ];

        $result = $this->putJson('/api/ec/poi/update/'.$ecPoi->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $geom = EcPoi::where('id', '=', $ecPoi->id)
            ->select(
                DB::raw('ST_AsGeoJSON(geometry) as geom')
            )
            ->first()
            ->geom;

        $this->assertEquals($newGeometry, json_decode($geom, true));
    }

    public function test_send_wheres_ids_update_where_relation()
    {
        $ecPoi = EcPoi::factory()->create();
        $where = TaxonomyWhere::factory()->create();

        $payload = [
            'where_ids' => [$where->id],
        ];
        $result = $this->putJson('/api/ec/poi/update/'.$ecPoi->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());

        $where = TaxonomyWhere::find($where->id);
        $pois = $where->ecPois;
        $this->assertCount(1, $pois);
        $this->assertSame($ecPoi->id, $pois->first()->id);
    }

    public function test_api_route()
    {
        $poi = EcPoi::factory()->create();
        $uri = $this->getJson('/api/ec/poi/'.$poi->id);
        $name = $this->get(route('api.ec.poi.json', ['id' => $poi->id]));
        $this->assertJsonStringEqualsJsonString($uri->getContent(), $name->getContent());
    }

    public function test_update_ele()
    {
        $ecPoi = EcPoi::factory()->create(['ele' => -10000]);
        $this->assertEquals(-10000, $ecPoi->ele);
        $payload = [
            'ele' => 100,
        ];

        $result = $this->putJson('/api/ec/poi/update/'.$ecPoi->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $ecPoiUpdated = EcPoi::find($ecPoi->id);

        $this->assertEquals(100, $ecPoiUpdated->ele);

        $json = $this->_getJsonTrack('api.ec.poi.json');

        $this->assertArrayHasKey('properties', $json);
        $this->assertArrayHasKey('ele', $json['properties']);
        $this->assertEquals(100, $json['properties']['ele']);
    }

    protected function _getJsonTrack($route_name)
    {
        $poi = EcPoi::factory()->create(['ele' => 100]);
        $result = $this->get(route($route_name, ['id' => $poi->id]));
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertJson($result->getContent());
        $json = json_decode($result->getContent(), true);

        return $json;
    }
}
