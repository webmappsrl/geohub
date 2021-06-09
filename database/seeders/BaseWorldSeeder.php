<?php

namespace Database\Seeders;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Nova\TaxonomyWhere;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

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
class BaseWorldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // TODO: check that we are running in LOCAL environment if not DIE

        // TODO: check if HOQU (LOCAL) IS WORKING if not die

        // TODO: check that there is a LOCAL GEOMIXER working if not die

        // TODO: clean GEOHUB DB

        // TODO: clean HOQU

        // TODO: clean GEOMIXER DB

        // TODO: USERS

        // Add random all taxonomies but wheres (to be added with command)
        TaxonomyActivity::factory(10)->create();
        TaxonomyTarget::factory(10)->create();
        TaxonomyTheme::factory(10)->create();
        TaxonomyWhen::factory(10)->create();
        TaxonomyPoiType::factory(10)->create();

        // TODO: taxonomy where with command (must be enrtiched by geomixer through local HOQU and GEOMIXER instance)
        Artisan::call('geohub:import_and_sync',[
            'import_method'=>'regioni_italiane',
            '--shp' => 'geodata/italy_admin/Limiti01012021/Reg01012021/Reg01012021_WGS84'
        ]);

        // EC MEDIA
        EcMedia::factory(10)->create();

        // POIS
        EcPoi::factory(10)->create();

        // TRACKS
        EcTrack::factory(10)->create();

    }
}
