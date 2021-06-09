<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyWhen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhenTest extends TestCase
{
    use RefreshDatabase;

    public function testGetGeoJson()
    {
        $this->withoutExceptionHandling();
        $taxonomyWhen = TaxonomyWhen::factory()->create();
        $response = $this->get(route("api.taxonomy.when.json", ['id' => $taxonomyWhen->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testGetGeoJsonMissingId()
    {
        $response = $this->get(route("api.taxonomy.when.json", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testGetGeoJsonByIdentifier()
    {
        $taxonomyWhen = TaxonomyWhen::factory()->create();
        $response = $this->get(route("api.taxonomy.when.json.idt", ['identifier' => $taxonomyWhen->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }
}
