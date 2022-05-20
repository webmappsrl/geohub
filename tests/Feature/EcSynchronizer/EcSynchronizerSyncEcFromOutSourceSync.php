<?php

namespace Tests\Feature;

use App\Classes\EcSynchronizer\SyncEcFromOutSource;
use App\Models\EcTrack;
use App\Models\OutSourceFeature;
use App\Models\OutSourcePoi;
use App\Models\OutSourceTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class EcSynchronizerSyncEcFromOutSourceSync extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function when_method_sync_with_type_track_it_returns_array_of_ids()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $source1 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first'
            ],
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '2',
                'name' => 'second'
            ],
        ]);
        $source3 = OutSourceTrack::factory()->create([
            'provider' => 'App\Providers\OutSourceOSMProvider',
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourceTrack::factory()->create([
            'provider' => 'App\Providers\OutSourceOSMProvider',
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi'
        ]);

        $user = User::factory()->create();
        
        $type = 'track';
        $author = $user->email;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$poi_type,$name_format,$app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);
        $this->assertNotEmpty($new_ec_features_id);
    }
    
    /**
     * @test
     */
    public function when_type_track_compare_ec_feature_out_source_feature_id_with_out_source_feature_id_return_true()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $source1 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first'
            ],
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '2',
                'name' => 'second'
            ],
        ]);
        $source3 = OutSourceTrack::factory()->create([
            'provider' => 'App\Providers\OutSourceOSMProvider',
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourceTrack::factory()->create([
            'provider' => 'App\Providers\OutSourceOSMProvider',
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi'
        ]);

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$poi_type,$name_format,$app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);
        foreach ($new_ec_features_id as $id) {
            if ($type == 'track') {
                $ec = EcTrack::find($id)->first();
                $this->assertContains( $ec->out_source_feature_id, $ids_array );
            }
        }
    }
    
    /**
     * @test
     */
    public function when_check_ec_track_user_id_with_parameter_author_should_return_true()
    {

        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });


        $source1 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first'
            ],
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '2',
                'name' => 'second'
            ],
        ]);
        $source3 = OutSourceTrack::factory()->create([
            'provider' => 'App\Providers\OutSourceOSMProvider',
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourceTrack::factory()->create([
            'provider' => 'App\Providers\OutSourceOSMProvider',
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi'
        ]);

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$poi_type,$name_format,$app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(2,EcTrack::count());

        foreach (EcTrack::get()->pluck('user_id')->toArray() as $ecTrack_user_id) {
            $this->assertEquals($user->id,$ecTrack_user_id);
        }
    }

    /**
     * @test
     */
    public function when_check_ec_track_name_with_parameter_name_format_should_create_proper_ec_track()
    {

        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });


        $source1 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first'
            ],
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '2',
                'name' => 'second'
            ],
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi'
        ]);

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$poi_type,$name_format,$app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(2,EcTrack::count());

        $ecTrack_names = EcTrack::get()->pluck('name')->toArray();
        
        $this->assertContains('path 1 - first',$ecTrack_names);
        $this->assertContains('path 2 - second',$ecTrack_names);
    }
    
    /**
     * @test
     */
    public function with_parameter_activity_should_associate_proper_taxonomy()
    {

        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });


        $source1 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first'
            ],
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi'
        ]);
        
        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$poi_type,$name_format,$app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(1,EcTrack::count());

        $ecTrack = EcTrack::first();

        $this->assertContains($activity,$ecTrack->taxonomyActivities->pluck('identifier')->toArray());
    }

    /**
     * @test
     */
    public function when_method_sync_with_type_poi_it_returns_array_of_ids()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $source1 = OutSourcePoi::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'first'
            ],
        ]);
        $source2 = OutSourcePoi::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'second'
            ],
        ]);
        $source3 = OutSourcePoi::factory()->create([
            'provider' => 'App\Providers\OutSourceOSMProvider',
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourcePoi::factory()->create([
            'provider' => 'App\Providers\OutSourceOSMProvider',
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi'
        ]);

        $user = User::factory()->create();
        
        $type = 'poi';
        $author = $user->email;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$poi_type,$name_format,$app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);
        $this->assertNotEmpty($new_ec_features_id);
    }

    /**
     * @test
     */
    public function when_type_poi_compare_ec_feature_out_source_feature_id_with_out_source_feature_id_return_true()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $source1 = OutSourcePoi::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'first'
            ],
        ]);
        $source2 = OutSourcePoi::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'second'
            ],
        ]);
        $source3 = OutSourcePoi::factory()->create([
            'provider' => 'App\Providers\OutSourceOSMProvider',
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourcePoi::factory()->create([
            'provider' => 'App\Providers\OutSourceOSMProvider',
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking'
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi'
        ]);

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$poi_type,$name_format,$app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);
        foreach ($new_ec_features_id as $id) {
            if ($type == 'poi') {
                $ec = EcPoi::find($id)->first();
                $this->assertContains( $ec->out_source_feature_id, $ids_array );
            }
        }
    }
}
