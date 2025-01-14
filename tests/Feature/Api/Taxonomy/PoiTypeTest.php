<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyPoiType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PoiTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_json()
    {
        $taxonomyPoiType = TaxonomyPoiType::factory()->create();
        $response = $this->get(route('api.taxonomy.poi_type.json', ['id' => $taxonomyPoiType->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_get_json_missing_id()
    {
        $response = $this->get(route('api.taxonomy.poi_type.json', ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function test_get_json_by_identifier()
    {
        $taxonomyPoiType = TaxonomyPoiType::factory()->create();
        $response = $this->get(route('api.taxonomy.poi_type.json.idt', ['identifier' => $taxonomyPoiType->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_identifier_format()
    {
        $taxonomyPoiType = TaxonomyPoiType::factory()->create(['identifier' => "Testo dell'identifier di prova"]);
        $this->assertEquals($taxonomyPoiType->identifier, 'testo-dellidentifier-di-prova');
    }

    public function test_identifier_uniqueness()
    {
        TaxonomyPoiType::factory()->create(['identifier' => 'identifier']);
        $taxonomyPoiTypeSecond = TaxonomyPoiType::factory()->create(['identifier' => null]);
        $taxonomyPoiTypeThird = TaxonomyPoiType::factory()->create(['identifier' => null]);
        $this->assertEquals($taxonomyPoiTypeSecond->identifier, $taxonomyPoiTypeThird->identifier);
        $this->assertNull($taxonomyPoiTypeSecond->identifier);
        $this->assertNull($taxonomyPoiTypeThird->identifier);
        $this->expectException(ValidationException::class);
        TaxonomyPoiType::factory()->create(['identifier' => 'identifier']);
    }
}
