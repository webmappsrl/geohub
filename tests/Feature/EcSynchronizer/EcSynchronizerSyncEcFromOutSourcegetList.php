<?php

namespace Tests\Feature;

use App\Classes\EcSynchronizer\SyncEcFromOutSource;
use App\Models\OutSourceTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcSynchronizerSyncEcFromOutSourcegetList extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function when_method_get_list_it_returns_array_of_ids()
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
            'identifier' => 'hiking',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $type = 'track';
        $author = 'team@webmapp.it';
        $provider = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureWP';
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';
        $app = 1;

        // $this->expectException(Exception::class);
        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);

        $id_list = $SyncEcFromOutSource->getList();
        $this->assertContains($source1->id, $id_list);
        $this->assertContains($source2->id, $id_list);
        $this->assertNotContains($source3->id, $id_list);
        $this->assertNotContains($source4->id, $id_list);
    }
}
