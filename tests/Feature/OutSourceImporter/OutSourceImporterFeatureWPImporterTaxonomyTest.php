<?php

namespace Tests\Feature;

use App\Classes\OutSourceImporter\OutSourceImporterFeatureWP;
use App\Models\OutSourceFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class OutSourceImporterFeatureWPImporterTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function when_poi_has_webmapp_category_out_source_feature_tags_should_return_currect_identifier()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        // WHEN
        $type = 'poi';
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $source_id = 2654;
        $stelvio_poi = file_get_contents(base_path('tests/Feature/Stubs/stelvio_poi.json'));
        $url_poi = $endpoint.'/wp-json/wp/v2/poi/'.$source_id;
        
        $source_id_media = 2481;
        $stelvio_media = file_get_contents(base_path('tests/Feature/Stubs/stelvio_media.json'));
        $url_media = $endpoint.'/wp-json/wp/v2/media/'.$source_id_media;

        $mapping = file_get_contents(base_path('tests/Feature/Stubs/stelvio-wp-webmapp-it.json')); 

        // PREPARE MOCK
        $this->mock(CurlServiceProvider::class,function (MockInterface $mock) use ($stelvio_poi,$url_poi,$stelvio_media,$url_media){
            $mock->shouldReceive('exec')
            ->atLeast(1)
            ->with($url_poi)
            ->andReturn($stelvio_poi);
            
            $mock->shouldReceive('exec')
            ->atLeast(1)
            ->with($url_media)
            ->andReturn($stelvio_media);
        });

        // FIRE
        $poi = new OutSourceImporterFeatureWP($type,$endpoint,$source_id);
        $poi_id = $poi->importFeature();


        // VERIFY
        $out_source_poi = OutSourceFeature::find($poi_id);
      
        $this->assertEquals($out_source_poi->tags['poi_type'][0],'poi');
    }

    /** @test */
    public function when_track_has_activity_out_source_feature_tags_should_return_currect_identifier()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });
        
        // WHEN
        $type = 'track';
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $source_id = 6;
        $stelvio_track = file_get_contents(base_path('tests/Feature/Stubs/stelvio_track.json'));
        $url_track = $endpoint.'/wp-json/wp/v2/track/'.$source_id;
        
        $source_id_media = 1529;
        $stelvio_media = file_get_contents(base_path('tests/Feature/Stubs/stelvio_media_track.json'));
        $url_media = $endpoint.'/wp-json/wp/v2/media/'.$source_id_media;

        $mapping = file_get_contents(base_path('tests/Feature/Stubs/stelvio-wp-webmapp-it.json')); 

        // PREPARE MOCK
        $this->mock(CurlServiceProvider::class,function (MockInterface $mock) use ($stelvio_track,$url_track,$stelvio_media,$url_media){
            $mock->shouldReceive('exec')
            ->atLeast(1)
            ->with($url_track)
            ->andReturn($stelvio_track);
            
            $mock->shouldReceive('exec')
            ->atLeast(1)
            ->with($url_media)
            ->andReturn($stelvio_media);
        });

        // FIRE
        $poi = new OutSourceImporterFeatureWP($type,$endpoint,$source_id);
        $poi_id = $poi->importFeature();


        // VERIFY
        $out_source_poi = OutSourceFeature::find($poi_id);
      
        $this->assertEquals($out_source_poi->tags['activity'][0],'hiking');
    }
}
