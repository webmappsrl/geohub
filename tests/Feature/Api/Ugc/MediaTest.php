<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\TaxonomyWhere;
use App\Models\UgcMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testGetGeoJson()
    {
        $ugcMedia = UgcMedia::factory()->create();
        $response = $this->get(route("api.ugc.media.geojson", ['id' => $ugcMedia->id]));
        $this->assertSame(200, $response->status());
        $json = $response->json();
        $this->assertArrayHasKey('type', $json);
        $this->assertSame('Feature', $json["type"]);
    }

    public function testGetGeoJsonMissingId()
    {
        $response = $this->get(route("api.ugc.media.geojson", ['id' => 1]));
        $this->assertSame(404, $response->status());
    }

    public function testAssociateZeroTaxonomyWhereWithUgcMedia()
    {
        $media = UgcMedia::factory()->create();
        $response = $this->post(route("api.ugc.media.associate", ['id' => $media->id]));
        $this->assertSame(200, $response->status());

        $media = UgcMedia::find($media->id);
        $this->assertCount(0, $media->taxonomy_wheres);
    }

    public function testAssociateUnknownTaxonomyWhereWithUgcMedia()
    {
        $media = UgcMedia::factory()->create();
        $response = $this->post(route("api.ugc.media.associate", [
            'id' => $media->id,
            'name' => $this->faker->name(),
            'where_ids' => [12],

        ]));
        $this->assertSame(200, $response->status());

        $media = UgcMedia::find($media->id);
        $this->assertCount(0, $media->taxonomy_wheres);
    }

    public function testAssociateTaxonomyWhereWithUgcMedia()
    {
        $media = UgcMedia::factory()->create();
        $wheres = TaxonomyWhere::factory()->count(2)->create();
        $whereIds = $wheres->pluck('id')->toArray();
        $response = $this->post(route("api.ugc.media.associate", [
            'id' => $media->id,
            'name' => $this->faker->name(),
            'where_ids' => $whereIds,
        ]));
        $this->assertSame(200, $response->status());

        $media = UgcMedia::find($media->id);
        $this->assertCount(2, $media->taxonomy_wheres);
        $ids = $media->taxonomy_wheres->pluck('id')->toArray();
        $this->assertSame($whereIds, $ids);
    }
}
