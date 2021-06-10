<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyTheme;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function testGetJson()
    {
        $taxonomyTheme = TaxonomyTheme::factory()->create();
        $response = $this->get(route("api.taxonomy.theme.json", ['id' => $taxonomyTheme->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testGetJsonMissingId()
    {
        $response = $this->get(route("api.taxonomy.theme.json", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testGetJsonByIdentifier()
    {
        $taxonomyTheme = TaxonomyTheme::factory()->create();
        $response = $this->get(route("api.taxonomy.theme.json.idt", ['identifier' => $taxonomyTheme->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testIdentifierFormat()
    {
        $taxonomyTheme = TaxonomyTheme::factory()->create(['identifier' => "Testo dell'identifier di prova"]);
        $this->assertEquals($taxonomyTheme->identifier, "testo-dellidentifier-di-prova");
    }

    public function testIdentifierUniqueness()
    {
        TaxonomyTheme::factory()->create(['identifier' => "identifier"]);
        $taxonomyThemeSecond = TaxonomyTheme::factory()->create(['identifier' => NULL]);
        $taxonomyThemeThird = TaxonomyTheme::factory()->create(['identifier' => NULL]);
        $this->assertEquals($taxonomyThemeSecond->identifier, $taxonomyThemeThird->identifier);
        $this->assertNull($taxonomyThemeSecond->identifier);
        $this->assertNull($taxonomyThemeThird->identifier);

        try {
            TaxonomyTheme::factory()->create(['identifier' => "identifier"]);
        } catch (Exception $e) {
            $this->assertEquals($e->getCode(), '23505', "SQLSTATE[23505]: Unique violation error");
        }
    }
}
