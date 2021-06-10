<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyWhen;
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
}
