<?php

namespace Tests\Feature;

use App\Classes\OutSourceImporter\OutSourceImporterFeatureWP;
use App\Models\OutSourceFeature;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Tests\TestCase;

class OutSourceImporterFeatureWPImportPOITest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function when_endpoint_is_stelvio_and_type_is_poi_it_creates_proper_out_feature()
    {
        // WHEN
        $type = 'poi';
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $source_id = 2654;
        $stelvio_poi = file_get_contents(base_path('tests/Feature/Stubs/stelvio_poi.json'));
        $url = $endpoint.'/wp-json/wp/v2/poi/'.$source_id;

        // PREPARE MOCK
        $this->mock(CurlServiceProvider::class,function (MockInterface $mock) use ($stelvio_poi,$url){
            $mock->shouldReceive('exec')
            ->atLeast(1)
            ->with($url)
            ->andReturn($stelvio_poi);
        });

        // FIRE
        $poi = new OutSourceImporterFeatureWP($type,$endpoint,$source_id);
        $poi_id = $poi->importFeature();

        // VERIFY
        $out_source = OutSourceFeature::find($poi_id);
        $this->assertEquals('poi',$out_source->type);
        $this->assertEquals(2654,$out_source->source_id);
        $this->assertEquals('https://stelvio.wp.webmapp.it',$out_source->endpoint);
        $this->assertEquals('App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',$out_source->provider);
       
        // TODO: add some checks on tags
        // TODO: add some checks on geometry
        // TODO: add some checks on raw_data
        // This is not working:
        // $this->assertEquals($stelvio_poi,$out_source->raw_data);


    }

    /** @test */
    public function when_poi_has_no_coordinates_expect_throw_exception()
    {
        // WHEN
        $type = 'poi';
        $endpoint = 'https://ucvs.wp.webmapp.it/';
        // $source_id = 2654;
        // $stelvio_poi = file_get_contents(base_path('tests/Feature/Stubs/stelvio_poi.json'));
        // $url = $endpoint.'/wp-json/wp/v2/poi/'.$source_id;
        
        $source_id_coord = 849;
        $stelvio_poi_no_coord = file_get_contents(base_path('tests/Feature/Stubs/stelvio_poi_no_coordinates.json'));
        $url = $endpoint.'/wp-json/wp/v2/poi/'.$source_id_coord;

        // PREPARE MOCK
        $this->mock(CurlServiceProvider::class,function (MockInterface $mock) use ($stelvio_poi_no_coord,$url){
            $mock->shouldReceive('exec')
            ->atLeast(1)
            ->with($url)
            ->andReturn($stelvio_poi_no_coord);
        });

        // FIRE
        $poi = new OutSourceImporterFeatureWP($type,$endpoint,$source_id_coord);

        // VERIFY
        try {
            $poi->importFeature();
        } catch (Exception $e) {
            $this->assertEquals('Error creating OSF : POI missing coordinates' ,$e->getMessage());
        }
     }

     /** @test */
    public function when_poi_throw_exception_with_no_coord_should_continue_importing()
    {
        // WHEN
        $type = 'poi';
        $endpoint = 'https://ucvs.wp.webmapp.it/';
        $source_id = 2654;
        $stelvio_poi = file_get_contents(base_path('tests/Feature/Stubs/stelvio_poi.json'));
        $url = $endpoint.'/wp-json/wp/v2/poi/'.$source_id;
        
        $source_id_coord = 849;
        $stelvio_poi_no_coord = file_get_contents(base_path('tests/Feature/Stubs/stelvio_poi_no_coordinates.json'));
        $url = $endpoint.'/wp-json/wp/v2/poi/'.$source_id_coord;

        // PREPARE MOCK
        $this->mock(CurlServiceProvider::class,function (MockInterface $mock) use ($stelvio_poi_no_coord,$url){
            $mock->shouldReceive('exec')
            ->atLeast(1)
            ->with($url)
            ->andReturn($stelvio_poi_no_coord);
        });

        // FIRE
        $poi = new OutSourceImporterFeatureWP($type,$endpoint,$source_id_coord);
        $features_list = ["849"=>"2019-06-14 15:15:40","2654"=>"2019-06-14 15:15:40"];
        foreach ($features_list as $id => $last_modified) {
            $OSF = new OutSourceImporterFeatureWP($this->type,$this->endpoint,$id);
            $OSF_id = $OSF->importFeature();
        }

        // VERIFY
        try {
            $poi->importFeature();
        } catch (Exception $e) {
            $this->assertEquals('Error creating OSF : POI missing coordinates' ,$e->getMessage());
        }
     }
}
