<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyPoiType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoiTypeTest extends TestCase
{
    use RefreshDatabase;

    public function testGetGeoJson()
    {
        $taxonomyPoiType = TaxonomyPoiType::factory()->create();
        $response = $this->get(route("api.taxonomy.poi_type.json", ['id' => $taxonomyPoiType->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testGetGeoJsonMissingId()
    {
        $response = $this->get(route("api.taxonomy.poi_type.json", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testGetGeoJsonByIdentifier()
    {
        $taxonomyPoiType = TaxonomyPoiType::factory()->create();
        $response = $this->get(route("api.taxonomy.poi_type.json.idt", ['identifier' => $taxonomyPoiType->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }
}
