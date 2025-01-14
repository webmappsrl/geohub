<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_json()
    {
        $this->withoutExceptionHandling();
        $taxonomyActivity = TaxonomyActivity::factory()->create();
        $response = $this->get(route('api.taxonomy.activity.json', ['id' => $taxonomyActivity->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_get_json_missing_id()
    {
        $response = $this->get(route('api.taxonomy.activity.json', ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function test_get_json_by_identifier()
    {
        $taxonomyActivity = TaxonomyActivity::factory()->create();
        $response = $this->get(route('api.taxonomy.activity.json.idt', ['identifier' => $taxonomyActivity->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_identifier_format()
    {
        $taxonomyActivity = TaxonomyActivity::factory()->create(['identifier' => "Testo dell'identifier di prova"]);
        $this->assertEquals($taxonomyActivity->identifier, 'testo-dellidentifier-di-prova');
    }

    public function test_identifier_uniqueness()
    {
        TaxonomyActivity::factory()->create(['identifier' => 'identifier']);
        $taxonomyActivitySecond = TaxonomyActivity::factory()->create(['identifier' => null]);
        $taxonomyActivityThird = TaxonomyActivity::factory()->create(['identifier' => null]);
        $this->assertEquals($taxonomyActivitySecond->identifier, $taxonomyActivityThird->identifier);
        $this->assertNull($taxonomyActivitySecond->identifier);
        $this->assertNull($taxonomyActivityThird->identifier);
        $this->expectException(ValidationException::class);
        TaxonomyActivity::factory()->create(['identifier' => 'identifier']);

    }
}
