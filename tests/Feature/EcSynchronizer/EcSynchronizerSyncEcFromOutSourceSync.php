<?php

namespace Tests\Feature;

use App\Classes\EcSynchronizer\SyncEcFromOutSource;
use App\Models\EcTrack;
use App\Models\OutSourceTrack;
use App\Models\TaxonomyActivity;
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
    public function when_method_sync_it_returns_array_of_ids()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $source1 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
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

        $user = User::factory()->create();
        
        $type = 'track';
        $author = $user->email;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);
        $this->assertNotEmpty($new_ec_features_id);
    }
    
    /**
     * @test
     */
    public function when_compare_ec_feature_out_source_feature_id_with_out_source_feature_id_return_true()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $source1 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
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

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);
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
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
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

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(2,EcTrack::count());

        foreach (EcTrack::get()->pluck('user_id')->toArray() as $ecTrack_user_id) {
            $this->assertEquals($user->id,$ecTrack_user_id);
        }
    }
}
