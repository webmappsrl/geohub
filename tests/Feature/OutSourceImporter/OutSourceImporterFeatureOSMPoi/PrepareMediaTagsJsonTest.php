<?php

use App\Classes\OutSourceImporter\OutSourceImporterFeatureOSMPoi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrepareMediaTagsJsonTest extends TestCase
{
    /**
     * Test that the initial OSM POI import downloads media with the configured User-Agent.
     *
     * @return void
     */
    public function test_media_download_uses_configured_user_agent()
    {
        // config('geohub.osf_media_storage_name') resolves to a real S3 disk in this repo's
        // .env (no .env.testing override) — fake it so the test never hits real S3.
        Storage::fake(config('geohub.osf_media_storage_name'));

        Http::fake([
            'upload.wikimedia.org/*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/EcMedia/test_resize.jpg')),
                200
            ),
        ]);

        $importer = new OutSourceImporterFeatureOSMPoi('poi', 'osmpoi:test', 'node/999', false, Log::channel('stack'));

        $media = [
            'query' => [
                'pages' => [
                    '1' => [
                        'title' => 'File:Test-image.jpg',
                        'imageinfo' => [
                            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/t/te/Test-image.jpg'],
                        ],
                    ],
                ],
            ],
        ];

        $importer->prepareMediaTagsJson($media);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'upload.wikimedia.org')
                && $request->hasHeader('User-Agent', config('geohub.wikimedia_user_agent'));
        });
    }
}
