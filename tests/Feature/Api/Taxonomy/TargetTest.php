<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyTarget;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetTest extends TestCase
{
    use RefreshDatabase;

    public function testGetJson()
    {
        $taxonomyTarget = TaxonomyTarget::factory()->create();
        $response = $this->get(route("api.taxonomy.target.json", ['id' => $taxonomyTarget->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testGetJsonMissingId()
    {
        $response = $this->get(route("api.taxonomy.target.json", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testGetJsonByIdentifier()
    {
        $taxonomyTarget = TaxonomyTarget::factory()->create();
        $response = $this->get(route("api.taxonomy.target.json.idt", ['identifier' => $taxonomyTarget->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testIdentifierFormat()
    {
        $taxonomyTarget = TaxonomyTarget::factory()->create(['identifier' => "Testo dell'identifier di prova"]);
        $this->assertEquals($taxonomyTarget->identifier, "testo-dellidentifier-di-prova");
    }

    public function testIdentifierUniqueness()
    {
        TaxonomyTarget::factory()->create(['identifier' => "identifier"]);
        $taxonomyTargetSecond = TaxonomyTarget::factory()->create(['identifier' => NULL]);
        $taxonomyTargetThird = TaxonomyTarget::factory()->create(['identifier' => NULL]);
        $this->assertEquals($taxonomyTargetSecond->identifier, $taxonomyTargetThird->identifier);
        $this->assertNull($taxonomyTargetSecond->identifier);
        $this->assertNull($taxonomyTargetThird->identifier);

        try {
            TaxonomyTarget::factory()->create(['identifier' => "identifier"]);
        } catch (Exception $e) {
            $this->assertEquals($e->getCode(), '23505', "SQLSTATE[23505]: Unique violation error");
        }
    }
}
