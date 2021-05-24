<?php

namespace Tests\Feature\Api\Ec;

use App\Http\Controllers\EditorialContentController;
use App\Models\EcMedia;
use App\Providers\HoquJobs\TaxonomyWhereJobsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function testUpdateEcMedia()
    {

        $ecMedia = EcMedia::factory(1)->create(['url' => 'test']);
        $payload = [
            'exif' => [
                "FileName" => "test.jpg",
            ],
            'geometry' => [10.448261111111, 43.781288888889],
            'url' => "https://ecmedia.s3.eu-central-1.amazonaws.com/EcMedia/test.jpg",
        ];

        $ecControlelr = $this->partialMock(EditorialContentController::class);

        $ecControlelr->enrichEcImage($payload, $ecMedia->id);

    }
}
