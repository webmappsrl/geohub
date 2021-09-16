<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\User;
use App\Nova\TaxonomyWhere;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

/**
 * Class BaseWorldSeeder
 *
 * First class of test world seeder. These classe are uset to create a real scenario
 * with real data to be used in real e2e test
 *
 * Use command
 *
 * php artisan
 *
 * @package Database\Seeders
 */
class BaseWorldSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        // TODO: check that we are running in LOCAL environment if not DIE

        // TODO: check if HOQU (LOCAL) IS WORKING if not die

        // TODO: check that there is a LOCAL GEOMIXER working if not die

        // TODO: clean GEOHUB DB

        // TODO: clean HOQU

        // TODO: clean GEOMIXER DB

        // TODO: USERS

        // Add random all taxonomies but wheres (to be added with command)
        $hiking = TaxonomyActivity::factory()->create([
            'name' => 'Camminata',
            'identifier' => 'hiking',
        ]);
        $cycling = TaxonomyActivity::factory()->create([
            'name' => 'Cicloturismo',
            'identifier' => 'cycling',
        ]);
        TaxonomyActivity::factory(8)->create();
        TaxonomyTarget::factory(10)->create();
        TaxonomyTheme::factory(10)->create();
        TaxonomyWhen::factory(10)->create();
        TaxonomyPoiType::factory(10)->create();

        // TODO: taxonomy where with command (must be enrtiched by geomixer through local HOQU and GEOMIXER instance)
        Artisan::call('geohub:import_and_sync', [
            'import_method' => 'regioni_italiane',
            '--shp' => 'geodata/italy_admin/Limiti01012021/Reg01012021/Reg01012021_WGS84'
        ]);

        // EC MEDIA
        EcMedia::factory(10)->create();

        // POIS
        EcPoi::factory(10)->create();

        // TRACKS
        // Create a track in the test dem, then generate some more tracks
        $geom = '{"type":"LineString","coordinates":[[11.03,43.18,0],[11.05,43.14,0],[11.01,43.20,0],[11.06,43.17,0]]}';
        EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geom')")
        ])->create();
        EcTrack::factory(10)->create();

        // APP
        App::factory(10)->create();

        // Complex case for APP
        $user = User::where('email', '=', 'editor@webmapp.it')->first();
        if (is_null($user)) {
            $user = User::factory()->create([
                'email' => 'editor@webmapp.it',
                'name' => 'Editor Webmapp',
                'password' => bcrypt('webmapp'),
            ]);
            // Give Editor role
            $res = DB::select("SELECT id from roles where name='Editor'");
            $editor_id = $res[0]->id;
            $tableNames = config('permission.table_names');
            $modelHasRolesTableName = $tableNames['model_has_roles'];
            DB::table($modelHasRolesTableName)->insert([
                'role_id' => $editor_id,
                'model_id' => $user->id,
                'model_type' => User::class
            ]);
        }

        $activity = TaxonomyActivity::factory()->create();
        $media = EcMedia::factory()->create();
        $media1 = EcMedia::factory()->create();
        $media2 = EcMedia::factory()->create();
        $media3 = EcMedia::factory()->create();
        $media4 = EcMedia::factory()->create();
        $media5 = EcMedia::factory()->frontEndSizes()->create();

        $track = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(0 0 0, 1 1 1)'))")]);
        $track->user_id = $user->id;
        $track->featureImage()->associate($media);
        $track->ecMedia()->attach($media1);
        $track->ecMedia()->attach($media2);
        $track->save();
        $track->taxonomyActivities()->attach([$activity->id]);
        $track->taxonomyActivities()->attach([$hiking->id]);

        $track1 = EcTrack::factory()->create(['geometry' => DB::raw("(ST_GeomFromText('LINESTRING(2 2 2, 3 3 3)'))")]);
        $track1->user_id = $user->id;
        $track1->featureImage()->associate($media);
        $track1->ecMedia()->attach($media1);
        $track1->ecMedia()->attach($media2);
        $track1->save();
        $track1->taxonomyActivities()->attach([$activity->id]);
        $track1->taxonomyActivities()->attach([$hiking->id]);
        $track1->taxonomyActivities()->attach([$cycling->id]);

        $track2 = EcTrack::factory()->create([
            'name' => 'Frontend Track',
            'geometry' => DB::raw("(ST_GeomFromText('LINESTRING(2 2 2, 3 3 3)'))")
        ]);
        $track2->user_id = $user->id;
        $track2->featureImage()->associate($media5);
        $track2->ecMedia()->attach($media1);
        $track2->ecMedia()->attach($media2);
        $track2->ecMedia()->attach($media3);
        $track2->ecMedia()->attach($media4);
        $track2->save();
        $track2->taxonomyActivities()->attach([$activity->id]);
        $track2->taxonomyActivities()->attach([$hiking->id]);
        $track2->taxonomyActivities()->attach([$cycling->id]);
        $track2->taxonomyWheres()->attach([9]);
        $track2->taxonomyWheres()->attach([20]);

        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();

        Log::info("Access to http://geohub.test/api/app/elbrus/$app->id/taxonomies/activity.json to test it from browser.");
        Log::info("Access to http://geohub.test/api/app/elbrus/$app->id/taxonomies/track_activity_$activity->id.json to test it from browser.");
        Log::info("Access to http://geohub.test/api/app/elbrus/$app->id/config.json to test it from browser.");
        Log::info("Access to http://geohub.test/api/app/elbrus/$app->id/geojson/ec_track_$track1->id.json to test it from browser.");
        Log::info("Access to http://geohub.test/api/ec/track/$track1->id to test it from browser.");
        Log::info("Access to http://geohub.test/api/ec/track/$track1->id.geojson to test it from browser.");
    }
}
