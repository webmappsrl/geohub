<?php

namespace Tests\Feature\Api\Taxonomy;

use App\Models\TaxonomyActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function testGetJson()
    {
        $this->withoutExceptionHandling();
        $taxonomyActivity = TaxonomyActivity::factory()->create();
        $response = $this->get(route("api.taxonomy.activity.json", ['id' => $taxonomyActivity->id]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }

    public function testGetJsonMissingId()
    {
        $response = $this->get(route("api.taxonomy.activity.json", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testGetJsonByIdentifier()
    {
        $taxonomyActivity = TaxonomyActivity::factory()->create();
        $response = $this->get(route("api.taxonomy.activity.json.idt", ['identifier' => $taxonomyActivity->identifier]));
        $this->assertSame(200, $response->status());
        $this->assertIsObject($response);
    }
}
