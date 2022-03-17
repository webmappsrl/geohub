<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyWhere;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MontePisanoSeeder extends Seeder
{

    private $taxonomy_activity_hiking;
    private $taxonomy_activity_cycling;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Import Poi Types
        $this->importAllPoiTypes();

        // Import WHERE
        $this->importAllWhere();

        // Import WHERE
        $this->importAllActivities();

        // Import Tracks
        $this->importTracks();

        // Create Apps
        $this->createApps();

    }

    private function importAllActivities() {
        $this->taxonomy_activity_hiking = TaxonomyActivity::factory()->create(['identifier'=>'hiking','name'=>'Escursionismo']);
        $this->taxonomy_activity_cycling = TaxonomyActivity::factory()->create(['identifier'=>'cycling','name'=>'In Bicicletta']);
    }

    private function importAllWhere() {
        // Toscana
        $this->importWhere('toscana');
        // Provincia di Lucca
        $this->importWhere('provincia_lucca');
        // Provincia di Pisa
        $this->importWhere('provincia_pisa');
        // Comune di San Giuliano Terme
        $this->importWhere('comune_san_giuliano_terme');
        // Comune di Calci
        $this->importWhere('comune_calci');
        // Comune di Buti
        $this->importWhere('comune_buti');
        // Comune di Vicopisano
        $this->importWhere('comune_vicopisano');
        // Comune di Capannori
        $this->importWhere('comune_capannori');
        // Comune di Lucca
        $this->importWhere('comune_lucca');
        // Comune di Vecchiano 
        $this->importWhere('comune_vecchiano');
        // Comune di Pisa
        $this->importWhere('comune_pisa');

    }

    private function importWhere($name) {
        $path = base_path().'/tests/Fixtures/MontePisano/where/'.$name.'.geojson';
        if(file_exists($path)) {
            Log::info("Processing $path");
            $g = json_decode(file_get_contents($path));
            TaxonomyWhere::factory()->create(
                [
                    'name' => $g->properties->name->it,
                    'geometry' => DB::raw("ST_GeomFromGeoJSON('".json_encode($g->geometry)."')"),
                ]
            );
        }
        else {
            Log::info("Warning $path does not exists... SKIPPING!!");
        }
    }

    private function importAllPoiTypes() {
        $this->importPoiType('Cultura','culture');
        $this->importPoiType('Natura','nature');
        $this->importPoiType('Centro Visite','visitor-center');
        $this->importPoiType('Informazioni','tourist-information');
        // $this->importPoiType('','');
    }

    private function importPoiType($name,$identifier) {
        TaxonomyPoiType::factory()->create(
            [
                'name' => $name,
                'identifier' => $identifier,
            ]
        );
    }

    /**
     * Create tracks from https://overpass-turbo.eu/s/1f5e
     *
     * @return void
     */
    private function importTracks() {
        $path = base_path().'/tests/Fixtures/MontePisano/tracks.geojson';
        if(file_exists($path)) {
            Log::info("Processing TRACKS");
            $g = json_decode(file_get_contents($path));
            foreach($g->features as $track) {
                $t = EcTrack::factory()->create([
                    'name' => isset($track->properties->name) ? $track->properties->name : 'ND',
                    'ref' => isset($track->properties->ref) ? $track->properties->ref : 'ND',
                    'geometry' => DB::raw("ST_Force3D(ST_GeomFromGeoJSON('".json_encode($track->geometry)."'))"),
                ]);
                $t->TaxonomyActivities()->attach($this->taxonomy_activity_cycling);
                $t->TaxonomyActivities()->attach($this->taxonomy_activity_hiking);
                $t->save();
            }
        }
        else {
            Log::info("Warning $path does not exists... SKIPPING!!");
        }

    }

    private function createApps() {
        $app_elbrus = App::factory()->create(['api'=>'elbrus']);
        $app_webmapp = App::factory()->create(['api'=>'webmapp']);
        $app_webapp = App::factory()->create(['api'=>'webapp']);
    }
}
