<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_json()
    {
        $taxonomyTheme = TaxonomyTheme::factory()->create();
        $response = $this->get(route('api.taxonomy.theme.json', ['id' => $taxonomyTheme->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_get_json_missing_id()
    {
        $response = $this->get(route('api.taxonomy.theme.json', ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function test_get_json_by_identifier()
    {
        $taxonomyTheme = TaxonomyTheme::factory()->create();
        $response = $this->get(route('api.taxonomy.theme.json.idt', ['identifier' => $taxonomyTheme->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_identifier_format()
    {
        $taxonomyTheme = TaxonomyTheme::factory()->create(['identifier' => "Testo dell'identifier di prova"]);
        $this->assertEquals($taxonomyTheme->identifier, 'testo-dellidentifier-di-prova');
    }

    public function test_identifier_uniqueness()
    {
        TaxonomyTheme::factory()->create(['identifier' => 'identifier']);
        $taxonomyThemeSecond = TaxonomyTheme::factory()->create(['identifier' => null]);
        $taxonomyThemeThird = TaxonomyTheme::factory()->create(['identifier' => null]);
        $this->assertEquals($taxonomyThemeSecond->identifier, $taxonomyThemeThird->identifier);
        $this->assertNull($taxonomyThemeSecond->identifier);
        $this->assertNull($taxonomyThemeThird->identifier);
        $this->expectException(ValidationException::class);
        TaxonomyTheme::factory()->create(['identifier' => 'identifier']);
    }
}
