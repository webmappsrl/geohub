<?php

namespace Tests\Feature;

use App\Classes\OutSourceImporter\OutSourceImporterFeatureStorageCSV;
use App\Models\OutSourceFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OutSourceImporterFeatureStorageSCVImportPOITest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function when_endpoint_is_csv_and_type_is_poi_it_reads_proper_out_source_feature()
    {
        // WHEN
        $type = 'poi';
        $endpoint = '/importer/parco-maremma/esercizi.csv';
        $source_id = 1;

        // FIRE
        $poi = new OutSourceImporterFeatureStorageCSV($type,$endpoint,$source_id);
        $poi_id = $poi->importFeature();

        echo "\n";
        print_r('SALAM');
        print_r($poi);
        // VERIFY
        $out_source = OutSourceFeature::find($poi_id);
        $this->assertEquals('poi',$out_source->type);
        $this->assertEquals(1,$out_source->source_id);
        $this->assertEquals('/importer/parco-maremma/esercizi.csv',$out_source->endpoint);
        $this->assertEquals('App\Classes\OutSourceImporter\OutSourceImporterFeatureStorageCSV',$out_source->provider);
       
        // TODO: add some checks on tags
        // TODO: add some checks on geometry
        // TODO: add some checks on raw_data
        // This is not working:
        // $this->assertEquals($stelvio_poi,$out_source->raw_data);


    }
}
