<?php

namespace Tests\Feature;

use App\Classes\EcSynchronizer\SyncEcFromOutSource;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\OutSourcePoi;
use App\Models\OutSourceTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTheme;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

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
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first',
            ],
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '2',
                'name' => 'second',
            ],
        ]);
        $source3 = OutSourceTrack::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourceTrack::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
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

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
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
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first',
            ],
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '2',
                'name' => 'second',
            ],
        ]);
        $source3 = OutSourceTrack::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourceTrack::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
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

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);
        foreach ($new_ec_features_id as $id) {
            if ($type == 'track') {
                $ec = EcTrack::find($id)->first();
                $this->assertContains($ec->out_source_feature_id, $ids_array);
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
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first',
            ],
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '2',
                'name' => 'second',
            ],
        ]);
        $source3 = OutSourceTrack::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourceTrack::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
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

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(2, EcTrack::count());

        foreach (EcTrack::get()->pluck('user_id')->toArray() as $ecTrack_user_id) {
            $this->assertEquals($user->id, $ecTrack_user_id);
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
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first',
            ],
        ]);
        $source2 = OutSourceTrack::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '2',
                'name' => 'second',
            ],
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(2, EcTrack::count());

        $ecTrack_names = EcTrack::get()->pluck('name')->toArray();

        $this->assertContains('path 1 - first', $ecTrack_names);
        $this->assertContains('path 2 - second', $ecTrack_names);
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
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first',
            ],
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(1, EcTrack::count());

        $ecTrack = EcTrack::first();

        $this->assertContains($activity, $ecTrack->taxonomyActivities->pluck('identifier')->toArray());
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
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'first',
            ],
        ]);
        $source2 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'second',
            ],
        ]);
        $source3 = OutSourcePoi::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourcePoi::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
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

        $user = User::factory()->create();

        $type = 'poi';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
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
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'first',
            ],
        ]);
        $source2 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'second',
            ],
        ]);
        $source3 = OutSourcePoi::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourcePoi::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
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

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);
        foreach ($new_ec_features_id as $id) {
            if ($type == 'poi') {
                $ec = EcPoi::find($id)->first();
                $this->assertContains($ec->out_source_feature_id, $ids_array);
            }
        }
    }

    /**
     * @test
     */
    public function when_compare_ec_poi_user_id_with_parameter_author_should_return_true()
    {

        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $source1 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'first',
            ],
        ]);
        $source2 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'second',
            ],
        ]);
        $source3 = OutSourcePoi::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
            'endpoint' => 'https://osm.it',
            'type' => 'poi',
        ]);
        $source4 = OutSourcePoi::factory()->create([
            'provider' => \App\Providers\OutSourceOSMProvider::class,
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

        $user = User::factory()->create();

        $type = 'poi';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(2, EcPoi::count());

        foreach (EcPoi::get()->pluck('user_id')->toArray() as $ecTrack_user_id) {
            $this->assertEquals($user->id, $ecTrack_user_id);
        }
    }

    /**
     * @test
     */
    public function when_compare_ec_poi_geometry_with_parameter_geometry_should_return_true()
    {

        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $geometry1 = DB::select(DB::raw("SELECT (ST_GeomFromText('POINT(10.42 42.70)'))"))[0]->st_geomfromtext;
        $source1 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'first',
            ],
            'geometry' => $geometry1,
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $user = User::factory()->create();

        $type = 'poi';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(1, EcPoi::count());
        $ecPoi_geometry = EcPoi::first()->geometry;
        $ecpoi_geojson = DB::select("SELECT ST_AsGeojson('$ecPoi_geometry')")[0]->st_asgeojson;
        $geometry1_geojson = DB::select("SELECT ST_AsGeojson('$geometry1')")[0]->st_asgeojson;
        $this->assertEquals($geometry1_geojson, $ecpoi_geojson);
    }

    /**
     * @test
     */
    public function when_check_ec_poi_name_with_parameter_name_format_should_create_proper_ec_poi()
    {

        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $source1 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'first',
            ],
        ]);
        $source2 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'second',
            ],
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $user = User::factory()->create();

        $type = 'poi';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(2, EcPoi::count());

        $ecTrack_names = EcPoi::get()->pluck('name')->toArray();

        $this->assertContains('path - first', $ecTrack_names);
        $this->assertContains('path - second', $ecTrack_names);
    }

    /**
     * @test
     */
    public function with_parameter_poi_type_should_associate_proper_taxonomy()
    {

        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $source1 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'tags' => [
                'name' => 'first',
            ],
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $user = User::factory()->create();

        $type = 'poi';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = 'path - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(1, EcPoi::count());

        $ecPoi = EcPoi::first();

        $this->assertContains($poi_type, $ecPoi->taxonomyPoiTypes->pluck('identifier')->toArray());
    }

    /**
     * @test
     */
    public function when_sync_track_with_related_poi_ec_poi_ec_track_returns_true()
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $OSF_poi_1 = OutSourcePoi::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'poi',
            'source_id' => 2552,
        ]);

        $osf_track = OutSourceTrack::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'related_poi' => [$OSF_poi_1->id],
                'name' => 'first',
            ],
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $poi_type = 'poi';
        $name_format = '{name}';
        $app = 1;

        $sync_poi = new SyncEcFromOutSource('poi', $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $sync_poi->checkParameters();
        $ids_array = $sync_poi->getList();
        $new_poi = $sync_poi->sync($ids_array);

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();

        $new_track = $SyncEcFromOutSource->sync($ids_array);
        $ecTrack = EcTrack::find($new_track)->first();
        $ecPoi = EcPoi::find($new_poi)->first();

        $this->assertEquals($ecTrack->ecPois->first()->id, $new_poi[0]);
        $this->assertEquals($ecPoi->ecTracks->first()->id, $new_track[0]);
    }

    /**
     * @test
     */
    public function with_parameter_theme_should_associate_proper_taxonomy()
    {

        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });

        $source1 = OutSourceTrack::factory()->create([
            'provider' => \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class,
            'endpoint' => 'https://stelvio.wp.webmapp.it',
            'type' => 'track',
            'tags' => [
                'ref' => '1',
                'name' => 'first',
            ],
        ]);

        TaxonomyActivity::updateOrCreate([
            'name' => 'Hiking',
            'identifier' => 'hiking',
        ]);

        TaxonomyTheme::updateOrCreate([
            'name' => 'Hiking PEC',
            'identifier' => 'hiking-pec',
        ]);

        TaxonomyPoiType::updateOrCreate([
            'name' => 'Point Of Interest',
            'identifier' => 'poi',
        ]);

        $user = User::factory()->create();

        $type = 'track';
        $author = $user->email;
        $provider = \App\Classes\OutSourceImporter\OutSourceImporterFeatureWP::class;
        $endpoint = 'https://stelvio.wp.webmapp.it';
        $activity = 'hiking';
        $theme = 'hiking-pec';
        $poi_type = 'poi';
        $name_format = 'path {ref} - {name}';
        $app = 1;

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app, $theme);
        $SyncEcFromOutSource->checkParameters();
        $ids_array = $SyncEcFromOutSource->getList();
        $new_ec_features_id = $SyncEcFromOutSource->sync($ids_array);

        $this->assertEquals(1, EcTrack::count());

        $ecTrack = EcTrack::first();

        $this->assertContains($theme, $ecTrack->taxonomyThemes->pluck('identifier')->toArray());
    }
}
