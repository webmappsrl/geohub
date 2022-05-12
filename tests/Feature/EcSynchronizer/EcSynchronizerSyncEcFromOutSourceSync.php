<?php

namespace Tests\Feature;

use App\Classes\EcSynchronizer\SyncEcFromOutSource;
use App\Models\EcTrack;
use App\Models\OutSourceTrack;
use App\Models\TaxonomyActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EcSynchronizerSyncEcFromOutSourceSync extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function when_method_sync_it_returns_array_of_ids()
    {
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

        $type = 'track';
        $author = 'team@webmapp.it';
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';            
        $activity = 'hiking';            
        $name_format = 'path {ref} - {name}';            
        $app = 1; 

        // $this->expectException(Exception::class);
        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app);

        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);
        foreach ($new_ec_features_id as $id) {
            if ($type == 'track') {
                $ec = EcTrack::find($id)->first();
                $this->assertContains( $ec->out_source_feature_id, $ids_array );
            }
        }
    }
}
