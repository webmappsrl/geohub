<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyWhen;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhenTest extends TestCase
{
    use RefreshDatabase;

    public function testGetJson()
    {
        $this->withoutExceptionHandling();
        $taxonomyWhen = TaxonomyWhen::factory()->create();
        $response = $this->get(route("api.taxonomy.when.json", ['id' => $taxonomyWhen->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testGetJsonMissingId()
    {
        $response = $this->get(route("api.taxonomy.when.json", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testGetJsonByIdentifier()
    {
        $taxonomyWhen = TaxonomyWhen::factory()->create();
        $response = $this->get(route("api.taxonomy.when.json.idt", ['identifier' => $taxonomyWhen->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testIdentifierFormat()
    {
        $taxonomyWhen = TaxonomyWhen::factory()->create(['identifier' => "Testo dell'identifier di prova"]);
        $this->assertEquals($taxonomyWhen->identifier, "testo-dellidentifier-di-prova");
    }

    public function testIdentifierUniqueness()
    {
        TaxonomyWhen::factory()->create(['identifier' => "identifier"]);
        $taxonomyWhenSecond = TaxonomyWhen::factory()->create(['identifier' => NULL]);
        $taxonomyWhenThird = TaxonomyWhen::factory()->create(['identifier' => NULL]);
        $this->assertEquals($taxonomyWhenSecond->identifier, $taxonomyWhenThird->identifier);
        $this->assertNull($taxonomyWhenSecond->identifier);
        $this->assertNull($taxonomyWhenThird->identifier);

        try {
            TaxonomyWhen::factory()->create(['identifier' => "identifier"]);
        } catch (Exception $e) {
            $this->assertEquals($e->getCode(), '23505', "SQLSTATE[23505]: Unique violation error");
        }
    }
}
