<?php

namespace Tests\Feature;

use AddOutSourceFeatureToEcTracks;
use App\Classes\EcSynchronizer\SyncEcFromOutSource;
use App\Models\OutSourceFeature;
use App\Models\OutSourceTrack;
use App\Models\TaxonomyActivity;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EcSynchronizerSyncEcFromOutSourcecheckParameters extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function when_command_variables_checkParamteres_should_return_true()
    {
        OutSourceTrack::factory()->create
        ([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        $type = 'track';
        $author = 'team@webmapp.it';
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        // $this->expectException(Exception::class);
        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);

        $response = $SyncEcFromOutSource->checkParameters();
        $this->assertEquals(true,$response);
    }
    
    /**
     * @test
     */
    public function when_parameter_type_is_wrong_should_return_false()
    {
        OutSourceTrack::factory()->create
        ([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        $type = 'xxx';
        $author = 'team@webmapp.it';
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);

        try {
            $SyncEcFromOutSource->checkParameters();
        } catch (Exception $e) {
            $this->assertEquals('The value of parameter type: xxx is not currect',$e->getMessage());
        }

    }
    
    /**
     * @test
     */
    public function when_parameter_author_email_is_wrong_should_return_false()
    {
        OutSourceTrack::factory()->create
        ([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        $type = 'track';
        $author = 'xxx';
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);

        try {
            $SyncEcFromOutSource->checkParameters();
        } catch (Exception $e) {
            $this->assertEquals('No User found with this email xxx' ,$e->getMessage());
        }
    }
    
    /**
     * @test
     */
    public function when_parameter_author_id_is_wrong_should_return_false()
    {
        OutSourceTrack::factory()->create
        ([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        $type = 'track';
        $author = '10000000';
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);

        try {
            $SyncEcFromOutSource->checkParameters();
        } catch (Exception $e) {
            $this->assertEquals('No User found with this ID 10000000' ,$e->getMessage());
        }
    }
    
    /**
     * @test
     */
    public function when_parameter_provider_is_wrong_should_return_false()
    {
        OutSourceTrack::factory()->create
        ([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        $type = 'track';
        $author = 'team@webmapp.it';
        $provider = 'xxx';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);

        try {
            $SyncEcFromOutSource->checkParameters();
        } catch (Exception $e) {
            $this->assertEquals('The value of parameter provider xxx is not currect' ,$e->getMessage());
        }
    }
    
    /**
     * @test
     */
    public function when_parameter_endpoint_is_wrong_should_return_false()
    {
        OutSourceTrack::factory()->create
        ([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        $type = 'track';
        $author = 'team@webmapp.it';
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'xxx';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);

        try {
            $SyncEcFromOutSource->checkParameters();
        } catch (Exception $e) {
            $this->assertEquals('The value of parameter endpoint xxx is not currect' ,$e->getMessage());
        }
    }
    
    /**
     * @test
     */
    public function when_parameter_activity_is_wrong_should_return_false()
    {
        OutSourceTrack::factory()->create
        ([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        $type = 'track';
        $author = 'team@webmapp.it';
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'xxx';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);

        try {
            $SyncEcFromOutSource->checkParameters();
        } catch (Exception $e) {
            $this->assertEquals('The value of parameter activity xxx is not currect' ,$e->getMessage());
        }
    }
    
    /**
     * @test
     */
    public function when_parameter_name_format_is_wrong_should_return_false()
    {
        OutSourceTrack::factory()->create
        ([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        $type = 'track';
        $author = 'team@webmapp.it';
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {xxx}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);

        try {
            $SyncEcFromOutSource->checkParameters();
        } catch (Exception $e) {
            $this->assertEquals('The value of parameter {xxx} can not be found' ,$e->getMessage());
        }
    }
}
