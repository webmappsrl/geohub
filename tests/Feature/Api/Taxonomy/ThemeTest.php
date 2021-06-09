<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function testGetGeoJson()
    {
        $taxonomyTheme = TaxonomyTheme::factory()->create();
        $response = $this->get(route("api.taxonomy.theme.json", ['id' => $taxonomyTheme->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testGetGeoJsonMissingId()
    {
        $response = $this->get(route("api.taxonomy.theme.json", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testGetGeoJsonByIdentifier()
    {
        $taxonomyTheme = TaxonomyTheme::factory()->create();
        $response = $this->get(route("api.taxonomy.theme.json.idt", ['identifier' => $taxonomyTheme->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }
}
