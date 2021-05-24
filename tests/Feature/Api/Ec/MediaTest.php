<?php

namespace Tests\Feature\Api\Ec;

use App\Http\Controllers\EditorialContentController;
use App\Models\EcMedia;
use App\Models\TaxonomyWhere;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function testNoIdReturnCode404()
    {
        $result = $this->putJson('/api/ec/media/update/0', []);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testNoUrlReturnCode400()
    {
        $ecMedia = EcMedia::factory()->create();
        $result = $this->putJson('/api/ec/media/update/' . $ecMedia->id, []);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testSendUrlUpdateFieldUrl()
    {
        $ecMedia = EcMedia::factory()->create();

        $actualUrl = $ecMedia->url;
        $newUrl = $actualUrl . '_new';

        $payload = [
            'url' => $newUrl,
        ];

        $result = $this->putJson('/api/ec/media/update/' . $ecMedia->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $ecMediaUpdated = EcMedia::find($ecMedia->id);

        $this->assertEquals($newUrl, $ecMediaUpdated->url);

    }

    public function testSendCoordinatesUpdateFieldGeometry()
    {
        $ecMedia = EcMedia::factory()->create();
        $newGeometry = [
            'type' => 'Point',
            'coordinates' => [10, 45]
        ];

        $payload = [
            'geometry' => $newGeometry,
            'url' => 'test',
        ];

        $result = $this->putJson('/api/ec/media/update/' . $ecMedia->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $geom = EcMedia::where('id', '=', $ecMedia->id)
            ->select(
                DB::raw('ST_AsGeoJSON(geometry) as geom')
            )
            ->first()
            ->geom;

        $this->assertEquals($newGeometry, json_decode($geom, true));
    }

    public function testSendWheresIdsUpdateWhereRelation()
    {
        $ecMedia = EcMedia::factory()->create();
        $where = TaxonomyWhere::factory()->create();

        $payload = [
            'url' => 'test',
            'where_ids' => [$where->id],
        ];
        $result = $this->putJson('/api/ec/media/update/' . $ecMedia->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());

        $where = TaxonomyWhere::find($where->id);
        $medias = $where->ecMedia;
        $this->assertCount(1, $medias);
        $this->assertSame($ecMedia->id, $medias->first()->id);
    }


}
