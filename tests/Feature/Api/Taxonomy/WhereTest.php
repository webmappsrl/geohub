<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyWhere;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WhereTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function test_get_geo_json()
    {
        $taxonomyWhere = TaxonomyWhere::factory()->create();
        $response = $this->get(route('api.taxonomy.where.geojson', ['id' => $taxonomyWhere->id]));
        $this->assertSame(200, $response->status());
        $json = $response->json();
        $this->assertArrayHasKey('type', $json);
        $this->assertSame('Feature', $json['type']);
    }

    public function test_get_geo_json_missing_id()
    {
        $response = $this->get(route('api.taxonomy.where.geojson', ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function test_get_geo_json_by_identifier()
    {
        $taxonomyWhere = TaxonomyWhere::factory()->create();
        $response = $this->get(route('api.taxonomy.where.geojson.idt', ['identifier' => $taxonomyWhere->identifier]));
        $this->assertSame(200, $response->status());
        $json = $response->json();
        $this->assertArrayHasKey('type', $json);
        $this->assertSame('Feature', $json['type']);
    }

    public function test_get_json()
    {
        $this->withoutExceptionHandling();
        $taxonomyWhen = TaxonomyWhere::factory()->create();
        $response = $this->get(route('api.taxonomy.where.json', ['id' => $taxonomyWhen->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_get_json_missing_id()
    {
        $response = $this->get(route('api.taxonomy.where.json', ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function test_get_json_by_identifier()
    {
        $taxonomyWhen = TaxonomyWhere::factory()->create();
        $response = $this->get(route('api.taxonomy.where.json.idt', ['identifier' => $taxonomyWhen->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function test_identifier_format()
    {
        $taxonomyWhere = TaxonomyWhere::factory()->create(['identifier' => "Testo dell'identifier di prova"]);
        $this->assertEquals($taxonomyWhere->identifier, 'testo-dellidentifier-di-prova');
    }

    public function test_identifier_uniqueness()
    {
        TaxonomyWhere::factory()->create(['identifier' => 'identifier']);
        $taxonomyWhereSecond = TaxonomyWhere::factory()->create(['identifier' => null]);
        $taxonomyWhereThird = TaxonomyWhere::factory()->create(['identifier' => null]);
        $this->assertEquals($taxonomyWhereSecond->identifier, $taxonomyWhereThird->identifier);
        $this->assertNull($taxonomyWhereSecond->identifier);
        $this->assertNull($taxonomyWhereThird->identifier);
        $this->expectException(ValidationException::class);
        TaxonomyWhere::factory()->create(['identifier' => 'identifier']);
    }
}
