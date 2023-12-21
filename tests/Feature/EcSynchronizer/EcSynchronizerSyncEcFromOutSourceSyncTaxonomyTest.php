<?php

namespace Tests\Feature;

use App\Classes\EcSynchronizer\SyncEcFromOutSource;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\OutSourcePoi;
use App\Models\OutSourceTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class EcSynchronizerSyncEcFromOutSourceSyncTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function when_sync_poi_with_new_webmapp_category_should_return_currect_poi_type()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $poi1 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'first poi',
                'poi_type' => ['poi', 'test'],
            ],
        ]);

        $storage = Storage::fake('mapping');
        $mapping = file_get_contents(base_path('tests/Feature/Stubs/stelvio-wp-webmapp-it.json'));
        $storage->put('stelvio-wp-webmapp-it.json', $mapping);
        Storage::shouldReceive('disk')
            ->with('mapping')
            ->andReturn($storage)
            ->shouldReceive('get')
            ->andReturn($mapping);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $type = 'poi';
        $author = 1;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = '{name}';
        $app = 1;

        // Sync poi
        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_poi = $SyncEcFromOutSource->sync($ids_array);
        $ectpoi = EcPoi::find($new_ec_poi);
        $this->assertContains('poi', $ectpoi[0]->taxonomyPoiTypes->pluck('identifier')->toArray());
        $this->assertContains('test', $ectpoi[0]->taxonomyPoiTypes->pluck('identifier')->toArray());
    }

    /**
     * @test
     */
    public function when_sync_track_with_new_activity_should_return_currect_geohub_activity()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $track1 = OutSourceTrack::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'name' => 'first track',
                'activity' => ['hiking', 'test'],
            ],
        ]);

        $storage = Storage::fake('mapping');
        $mapping = file_get_contents(base_path('tests/Feature/Stubs/stelvio-wp-webmapp-it.json'));
        $storage->put('stelvio-wp-webmapp-it.json', $mapping);
        Storage::shouldReceive('disk')
            ->with('mapping')
            ->andReturn($storage)
            ->shouldReceive('get')
            ->andReturn($mapping);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $type = 'track';
        $author = 1;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = '{name}';
        $app = 1;

        // Sync track
        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_track = $SyncEcFromOutSource->sync($ids_array);
        $ectTrack = EcTrack::find($new_ec_track);
        $this->assertContains('hiking', $ectTrack[0]->taxonomyActivities->pluck('identifier')->toArray());
        $this->assertContains('test', $ectTrack[0]->taxonomyActivities->pluck('identifier')->toArray());
    }

    /**
     * @test
     */
    public function when_sync_poi_with_webmapp_category_should_not_attach_category_in_command_option()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $poi1 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'first poi',
                'poi_type' => ['poi', 'test'],
            ],
        ]);

        $storage = Storage::fake('mapping');
        $mapping = file_get_contents(base_path('tests/Feature/Stubs/stelvio-wp-webmapp-it.json'));
        $storage->put('stelvio-wp-webmapp-it.json', $mapping);
        Storage::shouldReceive('disk')
            ->with('mapping')
            ->andReturn($storage)
            ->shouldReceive('get')
            ->andReturn($mapping);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'xxx',
            'identifier' => 'xxx',
        ]);

        $type = 'poi';
        $author = 1;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'xxx';
        $name_format = '{name}';
        $app = 1;

        // Sync poi
        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_poi = $SyncEcFromOutSource->sync($ids_array);
        $ectpoi = EcPoi::find($new_ec_poi);
        $this->assertNotContains('xxx', $ectpoi[0]->taxonomyPoiTypes->pluck('identifier')->toArray());
        $this->assertContains('poi', $ectpoi[0]->taxonomyPoiTypes->pluck('identifier')->toArray());
        $this->assertContains('test', $ectpoi[0]->taxonomyPoiTypes->pluck('identifier')->toArray());
    }

    /**
     * @test
     */
    public function when_sync_track_with_new_activity_should_not_attach_category_in_command_option()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $track1 = OutSourceTrack::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'name' => 'first track',
                'activity' => ['hiking', 'test'],
            ],
        ]);

        $storage = Storage::fake('mapping');
        $mapping = file_get_contents(base_path('tests/Feature/Stubs/stelvio-wp-webmapp-it.json'));
        $storage->put('stelvio-wp-webmapp-it.json', $mapping);
        Storage::shouldReceive('disk')
            ->with('mapping')
            ->andReturn($storage)
            ->shouldReceive('get')
            ->andReturn($mapping);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'xxx',
            'identifier' => 'xxx',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $type = 'track';
        $author = 1;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'xxx';
        $poi_type = 'poi';
        $name_format = '{name}';
        $app = 1;

        // Sync track
        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_track = $SyncEcFromOutSource->sync($ids_array);
        $ectTrack = EcTrack::find($new_ec_track);
        $this->assertNotContains('xxx', $ectTrack[0]->taxonomyActivities->pluck('identifier')->toArray());
        $this->assertContains('hiking', $ectTrack[0]->taxonomyActivities->pluck('identifier')->toArray());
        $this->assertContains('test', $ectTrack[0]->taxonomyActivities->pluck('identifier')->toArray());
    }
}
