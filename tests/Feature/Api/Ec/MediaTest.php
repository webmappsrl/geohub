<?php

namespace Tests\Feature\Api\Ec;

use App\Models\EcMedia;
use App\Models\TaxonomyWhere;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
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

    public function test_no_id_return_code404()
    {
        $result = $this->putJson('/api/ec/media/update/0', []);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function test_no_url_return_code400()
    {
        $ecMedia = EcMedia::factory()->create();
        $result = $this->putJson('/api/ec/media/update/'.$ecMedia->id, []);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function test_send_url_update_field_url()
    {
        $ecMedia = EcMedia::factory()->create();

        $actualUrl = $ecMedia->url;
        $newUrl = $actualUrl.'_new';

        $payload = [
            'url' => $newUrl,
        ];

        $result = $this->putJson('/api/ec/media/update/'.$ecMedia->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $ecMediaUpdated = EcMedia::find($ecMedia->id);

        $this->assertEquals($newUrl, $ecMediaUpdated->url);
    }

    public function test_send_coordinates_update_field_geometry()
    {
        $ecMedia = EcMedia::factory()->create();
        $newGeometry = [
            'type' => 'Point',
            'coordinates' => [10, 45],
        ];

        $payload = [
            'geometry' => $newGeometry,
            'url' => 'test',
        ];

        $result = $this->putJson('/api/ec/media/update/'.$ecMedia->id, $payload);

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

    public function test_send_wheres_ids_update_where_relation()
    {
        $ecMedia = EcMedia::factory()->create();
        $where = TaxonomyWhere::factory()->create();

        $payload = [
            'url' => 'test',
            'where_ids' => [$where->id],
        ];
        $result = $this->putJson('/api/ec/media/update/'.$ecMedia->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());

        $where = TaxonomyWhere::find($where->id);
        $medias = $where->ecMedia;
        $this->assertCount(1, $medias);
        $this->assertSame($ecMedia->id, $medias->first()->id);
    }

    public function test_send_thumbnails_to_ec_media()
    {
        $ecMedia = EcMedia::factory()->create();
        $payload = [
            'url' => 'test_url',
            'thumbnail_urls' => [['108x148' => 'url'], ['138x158' => 'url2']],
        ];
        $result = $this->putJson('/api/ec/media/update/'.$ecMedia->id, $payload);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $newEcMedia = EcMedia::find($ecMedia->id);

        $this->assertSame($newEcMedia->thumbnails, json_encode($payload['thumbnail_urls']));
    }

    public function test_delete_local_image()
    {
        $ecMedia = EcMedia::factory()->create();
        $payload = [
            'url' => 'https://ecmedia.s3.eu-central-1.amazonaws.com/EcMedia/Resize/100x200/test_100x200.jpg',
        ];
        $this->assertFileExists(Storage::disk('public')->path($ecMedia->url));

        $result = $this->putJson('/api/ec/media/update/'.$ecMedia->id, $payload);
        $ecMediaUpdated = EcMedia::find($ecMedia->id);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());

        $this->assertEquals($payload['url'], $ecMediaUpdated->url);
        $this->assertFileDoesNotExist(Storage::disk('public')->path($ecMedia->url));
    }
}
