<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyWhere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WhereTest extends TestCase
{
    use RefreshDatabase;

    public function testGetGeoJson()
    {

        $taxonomyWhere = TaxonomyWhere::factory()->create();
        $response = $this->get(route("api.taxonomy.where.geojson", ['id' => $taxonomyWhere->id]));
        $this->assertSame(200, $response->status());
        $json = $response->json();
        $this->assertArrayHasKey('type', $json);
        $this->assertSame('Feature', $json["type"]);
    }

    public function testGetGeoJsonMissingId()
    {
        $response = $this->get(route("api.taxonomy.where.geojson", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }
}
