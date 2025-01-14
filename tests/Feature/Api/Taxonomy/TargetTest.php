<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_json()
    {
        $taxonomyTarget = TaxonomyTarget::factory()->create();
        $response = $this->get(route('api.taxonomy.target.json', ['id' => $taxonomyTarget->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_get_json_missing_id()
    {
        $response = $this->get(route('api.taxonomy.target.json', ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function test_get_json_by_identifier()
    {
        $taxonomyTarget = TaxonomyTarget::factory()->create();
        $response = $this->get(route('api.taxonomy.target.json.idt', ['identifier' => $taxonomyTarget->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_identifier_format()
    {
        $taxonomyTarget = TaxonomyTarget::factory()->create(['identifier' => "Testo dell'identifier di prova"]);
        $this->assertEquals($taxonomyTarget->identifier, 'testo-dellidentifier-di-prova');
    }

    public function test_identifier_uniqueness()
    {
        TaxonomyTarget::factory()->create(['identifier' => 'identifier']);
        $taxonomyTargetSecond = TaxonomyTarget::factory()->create(['identifier' => null]);
        $taxonomyTargetThird = TaxonomyTarget::factory()->create(['identifier' => null]);
        $this->assertEquals($taxonomyTargetSecond->identifier, $taxonomyTargetThird->identifier);
        $this->assertNull($taxonomyTargetSecond->identifier);
        $this->assertNull($taxonomyTargetThird->identifier);
        $this->expectException(ValidationException::class);
        TaxonomyTarget::factory()->create(['identifier' => 'identifier']);
    }
}
