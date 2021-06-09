<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetTest extends TestCase
{
    use RefreshDatabase;

    public function testGetGeoJson()
    {
        $taxonomyTarget = TaxonomyTarget::factory()->create();
        $response = $this->get(route("api.taxonomy.target.json", ['id' => $taxonomyTarget->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testGetGeoJsonMissingId()
    {
        $response = $this->get(route("api.taxonomy.target.json", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testGetGeoJsonByIdentifier()
    {
        $taxonomyTarget = TaxonomyTarget::factory()->create();
        $response = $this->get(route("api.taxonomy.target.json.idt", ['identifier' => $taxonomyTarget->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }
}
