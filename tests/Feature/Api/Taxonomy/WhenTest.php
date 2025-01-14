<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyWhen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WhenTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_json()
    {
        $this->withoutExceptionHandling();
        $taxonomyWhen = TaxonomyWhen::factory()->create();
        $response = $this->get(route('api.taxonomy.when.json', ['id' => $taxonomyWhen->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_get_json_missing_id()
    {
        $response = $this->get(route('api.taxonomy.when.json', ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function test_get_json_by_identifier()
    {
        $taxonomyWhen = TaxonomyWhen::factory()->create();
        $response = $this->get(route('api.taxonomy.when.json.idt', ['identifier' => $taxonomyWhen->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_identifier_format()
    {
        $taxonomyWhen = TaxonomyWhen::factory()->create(['identifier' => "Testo dell'identifier di prova"]);
        $this->assertEquals($taxonomyWhen->identifier, 'testo-dellidentifier-di-prova');
    }

    public function test_identifier_uniqueness()
    {
        TaxonomyWhen::factory()->create(['identifier' => 'identifier']);
        $taxonomyWhenSecond = TaxonomyWhen::factory()->create(['identifier' => null]);
        $taxonomyWhenThird = TaxonomyWhen::factory()->create(['identifier' => null]);
        $this->assertEquals($taxonomyWhenSecond->identifier, $taxonomyWhenThird->identifier);
        $this->assertNull($taxonomyWhenSecond->identifier);
        $this->assertNull($taxonomyWhenThird->identifier);
        $this->expectException(ValidationException::class);
        TaxonomyWhen::factory()->create(['identifier' => 'identifier']);
    }
}
