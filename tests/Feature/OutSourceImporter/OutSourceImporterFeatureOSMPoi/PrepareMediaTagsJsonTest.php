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

    /**
     * Commons imageinfo urls now carry utm_* query parameters: they must not end up in the stored file name.
     *
     * @return void
     */
    public function test_media_file_name_ignores_the_url_query_string()
    {
        $storageName = config('geohub.osf_media_storage_name');
        Storage::fake($storageName);

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
                        'title' => 'File:It-pr-ldpx0125.jpg',
                        'imageinfo' => [
                            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/8/85/It-pr-ldpx0125.jpg?utm_source=commons.wikimedia.org&utm_campaign=imageinfo&utm_content=original'],
                        ],
                    ],
                ],
            ],
        ];

        $tags = $importer->prepareMediaTagsJson($media);

        $expected = sha1('It-pr-ldpx0125').'.jpg';
        $this->assertSame($expected, $tags['url']);
        Storage::disk($storageName)->assertExists($expected);
    }
}
