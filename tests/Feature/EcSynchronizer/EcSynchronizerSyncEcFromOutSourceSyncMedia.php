<?php

namespace Tests\Feature;

use App\Classes\EcSynchronizer\SyncEcFromOutSource;
use App\Models\EcMedia;
use App\Models\OutSourceFeature;
use App\Models\OutSourcePoi;
use App\Models\OutSourceTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Tests\TestCase;

class EcSynchronizerSyncEcFromOutSourceSyncMedia extends TestCase
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

        $source1 = OutSourcePoi::factory()->create([
            'provider' => 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP',
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'media',
            'tags' => [
                'url' => 'first.jpg',
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

        $users = User::all();
        
        $type = 'media';
        $author = 1;
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = '{name}';            
        $app = 1; 

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$poi_type,$name_format,$app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);
        $ecmedia = EcMedia::find($new_ec_features_id[0]);
        $this->assertNotEmpty($new_ec_features_id);
        $this->assertEquals($ecmedia->user_id,1);
    }
}
