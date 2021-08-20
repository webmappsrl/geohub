<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\UgcPoi;
use App\Models\TaxonomyWhere;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoiTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function testGetGeoJson() {
        $ugcPoi = UgcPoi::factory()->create();
        $response = $this->get(route("api.ugc.poi.geojson", ['id' => $ugcPoi->id]));
        $this->assertSame(200, $response->status());
        $json = $response->json();
        $this->assertArrayHasKey('type', $json);
        $this->assertSame('Feature', $json["type"]);
    }

    public function testGetGeoJsonMissingId() {
        $response = $this->get(route("api.ugc.poi.geojson", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testAssociateZeroTaxonomyWhereWithUgcPoi() {
        $poi = UgcPoi::factory()->create();
        $response = $this->post(route("api.ugc.poi.associate", ['id' => $poi->id]));
        $this->assertSame(200, $response->status());

        $poi = UgcPoi::find($poi->id);
        $this->assertCount(0, $poi->taxonomy_wheres);
    }

    public function testAssociateUnknownTaxonomyWhereWithUgcPoi() {
        $poi = UgcPoi::factory()->create();
        $response = $this->post(route("api.ugc.poi.associate", [
            'id' => $poi->id,
            "where_ids" => [12]
        ]));
        $this->assertSame(200, $response->status());

        $poi = UgcPoi::find($poi->id);
        $this->assertCount(0, $poi->taxonomy_wheres);
    }

    public function testAssociateTaxonomyWhereWithUgcPoi() {
        $poi = UgcPoi::factory()->create();
        $wheres = TaxonomyWhere::factory()->count(2)->create();
        $whereIds = $wheres->pluck('id')->toArray();
        $response = $this->post(route("api.ugc.poi.associate", [
            'id' => $poi->id,
            'where_ids' => $whereIds
        ]));
        $this->assertSame(200, $response->status());

        $poi = UgcPoi::find($poi->id);
        $this->assertCount(2, $poi->taxonomy_wheres);
        $ids = $poi->taxonomy_wheres->pluck('id')->toArray();
        $this->assertSame($whereIds, $ids);
    }
}
