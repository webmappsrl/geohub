<?php

namespace Tests\Feature;

use App\Classes\OutSourceImporter\OutSourceImporterFeatureWP;
use App\Models\OutSourceFeature;
use App\Models\OutSourcePoi;
use App\Providers\CurlServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class OutSourceImporterFeatureWPImportTrackTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function when_endpoint_is_stelvio_and_type_is_track_it_creates_proper_out_feature()
    {
        // WHEN
        $type = 'track';
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $source_id = 6;
        $source_id_en = 1239;
        $source_id_de = 1241;
        $media_id = 1529;
        $stelvio_track_it = file_get_contents(base_path('tests/Feature/Stubs/stelvio_track.json'));
        $stelvio_track_en = file_get_contents(base_path('tests/Feature/Stubs/stelvio_track_en.json'));
        $stelvio_track_de = file_get_contents(base_path('tests/Feature/Stubs/stelvio_track_de.json'));
        $stelvio_media = file_get_contents(base_path('tests/Feature/Stubs/stelvio_media.json'));
        $url_it = $endpoint.'/wp-json/wp/v2/track/'.$source_id;
        $url_en = $endpoint.'/wp-json/wp/v2/track/'.$source_id_en;
        $url_de = $endpoint.'/wp-json/wp/v2/track/'.$source_id_de;
        $url_media = $endpoint.'/wp-json/wp/v2/media/'.$media_id;
        $url_media2 = $endpoint.'/wp-json/wp/v2/media/2482';
        $url_media3 = $endpoint.'/wp-json/wp/v2/media/2483';
        $url_media4 = $endpoint.'/wp-json/wp/v2/media/2475';

        $storage = Storage::fake('mapping');
        $mapping = file_get_contents(base_path('tests/Feature/Stubs/stelvio-wp-webmapp-it.json'));
        $storage->put('stelvio-wp-webmapp-it.json', $mapping);
        Storage::shouldReceive('disk')
            ->with('mapping')
            ->andReturn($storage)
            ->shouldReceive('get')
            ->andReturn($mapping);

        // PREPARE MOCK ITA
        $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($stelvio_track_it, $url_it, $url_en, $stelvio_track_en, $url_de, $stelvio_track_de, $stelvio_media, $url_media, $url_media2, $url_media3, $url_media4) {
            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_it)
                ->andReturn($stelvio_track_it);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_en)
                ->andReturn($stelvio_track_en);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_de)
                ->andReturn($stelvio_track_de);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_media)
                ->andReturn($stelvio_media);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_media2)
                ->andReturn($stelvio_media);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_media3)
                ->andReturn($stelvio_media);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_media4)
                ->andReturn($stelvio_media);
        });

        // FIRE
        $track = new OutSourceImporterFeatureWP($type, $endpoint, $source_id);
        $track_id = $track->importFeature();

        // VERIFY
        $out_source = OutSourceFeature::find($track_id);
        $this->assertEquals('track', $out_source->type);
        $this->assertEquals(6, $out_source->source_id);
        $this->assertEquals('https://stelvio.wp.webmapp.it', $out_source->endpoint);
        $this->assertEquals(\App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class, $out_source->provider);

        // TODO: add some checks on tags
        $stelvio_track_js_it = json_decode($stelvio_track_it, true);
        $stelvio_track_js_en = json_decode($stelvio_track_en, true);
        $stelvio_track_js_de = json_decode($stelvio_track_de, true);

        $this->assertEquals($stelvio_track_js_it['title']['rendered'], $out_source->tags['name']['it']);

        // TODO: make it work with  &#8211; convertion to '-'
        // $this->assertEquals($stelvio_track_js_en['title']['rendered'],$out_source->tags['name']['en']);
        // $this->assertEquals($stelvio_track_js_de['title']['rendered'],$out_source->tags['name']['de']);

        $this->assertEquals($stelvio_track_js_it['content']['rendered'], $out_source->tags['description']['it']);
        $this->assertEquals(html_entity_decode($stelvio_track_js_en['content']['rendered']), $out_source->tags['description']['en']);
        $this->assertEquals(html_entity_decode($stelvio_track_js_de['content']['rendered']), $out_source->tags['description']['de']);

        // TODO: add some checks on geometry
        // TODO: add some checks on raw_data
        // This is not working:
        // $this->assertEquals($stelvio_track_it,json_encode($out_source->raw_data));

    }

    /** @test */
    public function when_track_has_related_poi_osf_track_should_return_ids_in_related_poi_tags()
    {
        // WHEN
        $type = 'track';
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $source_id = 6;
        $source_id_en = 1239;
        $source_id_de = 1241;
        $media_id = 1529;
        $stelvio_track_it = file_get_contents(base_path('tests/Feature/Stubs/stelvio_track.json'));
        $stelvio_track_en = file_get_contents(base_path('tests/Feature/Stubs/stelvio_track_en.json'));
        $stelvio_track_de = file_get_contents(base_path('tests/Feature/Stubs/stelvio_track_de.json'));
        $stelvio_media = file_get_contents(base_path('tests/Feature/Stubs/stelvio_media.json'));
        $url_it = $endpoint.'/wp-json/wp/v2/track/'.$source_id;
        $url_en = $endpoint.'/wp-json/wp/v2/track/'.$source_id_en;
        $url_de = $endpoint.'/wp-json/wp/v2/track/'.$source_id_de;
        $url_media = $endpoint.'/wp-json/wp/v2/media/'.$media_id;
        $url_media2 = $endpoint.'/wp-json/wp/v2/media/2482';
        $url_media3 = $endpoint.'/wp-json/wp/v2/media/2483';
        $url_media4 = $endpoint.'/wp-json/wp/v2/media/2475';

        $OSF_poi_1 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'source_id' => 2552,
        ]);
        $OSF_poi_2 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'source_id' => 2554,
        ]);
        $OSF_poi_3 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'source_id' => 2556,
        ]);
        $OSF_poi_4 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'source_id' => 2558,
        ]);

        $storage = Storage::fake('mapping');
        $mapping = file_get_contents(base_path('tests/Feature/Stubs/stelvio-wp-webmapp-it.json'));
        $storage->put('stelvio-wp-webmapp-it.json', $mapping);
        Storage::shouldReceive('disk')
            ->with('mapping')
            ->andReturn($storage)
            ->shouldReceive('get')
            ->andReturn($mapping);

        // PREPARE MOCK ITA
        $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($stelvio_track_it, $url_it, $url_en, $stelvio_track_en, $url_de, $stelvio_track_de, $stelvio_media, $url_media, $url_media2, $url_media3, $url_media4) {
            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_it)
                ->andReturn($stelvio_track_it);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_en)
                ->andReturn($stelvio_track_en);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_de)
                ->andReturn($stelvio_track_de);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_media)
                ->andReturn($stelvio_media);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_media2)
                ->andReturn($stelvio_media);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_media3)
                ->andReturn($stelvio_media);

            $mock->shouldReceive('exec')
                ->atleast(1)
                ->with($url_media4)
                ->andReturn($stelvio_media);
        });

        // FIRE
        $track = new OutSourceImporterFeatureWP($type, $endpoint, $source_id);
        $track_id = $track->importFeature();

        // VERIFY
        $out_source = OutSourceFeature::find($track_id);
        $this->assertEquals('track', $out_source->type);
        $this->assertEquals(6, $out_source->source_id);
        $this->assertEquals('https://stelvio.wp.webmapp.it', $out_source->endpoint);
        $this->assertEquals(\App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class, $out_source->provider);

        // add some checks on tags
        $this->assertContains($OSF_poi_1->id, $out_source->tags['related_poi']);
        $this->assertContains($OSF_poi_2->id, $out_source->tags['related_poi']);
        $this->assertContains($OSF_poi_3->id, $out_source->tags['related_poi']);
        $this->assertContains($OSF_poi_4->id, $out_source->tags['related_poi']);
    }
}
