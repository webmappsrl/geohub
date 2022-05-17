<?php

namespace Tests\Feature;

use App\Classes\OutSourceImporter\OutSourceImporterFeatureWP;
use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
        $stelvio_track = file_get_contents(base_path('tests/Feature/Stubs/stelvio_track.json'));
        $stelvio_track_en = file_get_contents(base_path('tests/Feature/Stubs/stelvio_track_en.json'));
        $stelvio_track_de = file_get_contents(base_path('tests/Feature/Stubs/stelvio_track_de.json'));
        $url = $endpoint.'/wp-json/wp/v2/track/'.$source_id;
        $url_en = $endpoint.'/wp-json/wp/v2/track/'.$source_id_en;
        $url_de = $endpoint.'/wp-json/wp/v2/track/'.$source_id_de;

        // PREPARE MOCK ITA
        $this->mock(CurlServiceProvider::class,function (MockInterface $mock) use ($stelvio_track,$url){
            $mock->shouldReceive('exec')
            ->atLeast(1)
            ->with($url)
            ->andReturn($stelvio_track);
        });

        // FIRE
        $track = new OutSourceImporterFeatureWP($type,$endpoint,$source_id);
        $track_id = $track->importFeature();

        // VERIFY
        $out_source = OutSourceFeature::find($track_id);
        $this->assertEquals('track',$out_source->type);
        $this->assertEquals(6,$out_source->source_id);
        $this->assertEquals('https://stelvio.wp.webmapp.it',$out_source->endpoint);
        $this->assertEquals('App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',$out_source->provider);
       
        // TODO: add some checks on tags
        // TODO: add some checks on geometry
        // TODO: add some checks on raw_data
        // This is not working:
        $this->assertEquals($stelvio_track,json_encode($out_source->raw_data));


    }

}
