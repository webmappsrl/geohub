<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\Layer;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\TaxonomyWhere;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MontePisanoSeeder extends Seeder
{
    private $taxonomy_activity_hiking;

    private $taxonomy_activity_cycling;

    private $taxonomy_theme_nature;

    private $taxonomy_theme_culture;

    private $taxonomy_when_spring;

    private $taxonomy_when_summer;

    private $taxonomy_when_autumn;

    private $taxonomy_when_winter;

    private $taxonomy_target_children;

    private $taxonomy_target_family;

    private $feature_image;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Taxonomies
        $this->importAllPoiTypes();
        $this->importAllWhere();
        $this->importAllActivities();
        $this->importAllThemes();
        $this->importAllWhens();
        $this->importAllTargets();

        // Images
        $this->importAllImages();

        // Features
        $this->importTracks();
        $this->createAppsAndLayers();
    }

    private function importAllWhere()
    {
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

    private function importAllPoiTypes()
    {
        $this->importPoiType('Cultura', 'culture');
        $this->importPoiType('Natura', 'nature');
        $this->importPoiType('Centro Visite', 'visitor-center');
        $this->importPoiType('Informazioni', 'tourist-information');
        // $this->importPoiType('','');
    }

    private function importAllActivities()
    {
        $this->taxonomy_activity_hiking = TaxonomyActivity::factory()->create(['identifier' => 'hiking', 'name' => 'Escursionismo']);
        $this->taxonomy_activity_cycling = TaxonomyActivity::factory()->create(['identifier' => 'cycling', 'name' => 'In Bicicletta']);
    }

    private function importAllThemes()
    {
        $this->taxonomy_theme_nature = TaxonomyTheme::factory()->create(['identifier' => 'nature', 'name' => 'Nature']);
        $this->taxonomy_theme_culture = TaxonomyTheme::factory()->create(['identifier' => 'culture', 'name' => 'Culture']);
    }

    private function importAllWhens()
    {
        $this->taxonomy_when_spring = TaxonomyWhen::factory()->create(['identifier' => 'spring', 'name' => 'Primavera']);
        $this->taxonomy_when_summer = TaxonomyWhen::factory()->create(['identifier' => 'summer', 'name' => 'Estate']);
        $this->taxonomy_when_autumn = TaxonomyWhen::factory()->create(['identifier' => 'autumn', 'name' => 'Autunno']);
        $this->taxonomy_when_winter = TaxonomyWhen::factory()->create(['identifier' => 'winter', 'name' => 'Inverno']);
    }

    private function importAllTargets()
    {
        $this->taxonomy_target_children = TaxonomyTarget::factory()->create(['identifier' => 'children', 'name' => 'Bambini']);
        $this->taxonomy_target_family = TaxonomyTarget::factory()->create(['identifier' => 'family', 'name' => 'Famiglie']);
        $this->taxonomy_target_sport = TaxonomyTarget::factory()->create(['identifier' => 'sport', 'name' => 'Sportivi']);
    }

    private function importAllImages()
    {
        Artisan::call('geohub:import_ec_media',
            ['url' => 'tests/Fixtures/EcMedia/test.jpg',
                'name' => 'TEST']);
        $this->feature_image = EcMedia::all()->first();
    }

    private function importWhere($name)
    {
        $path = base_path().'/tests/Fixtures/MontePisano/where/'.$name.'.geojson';
        if (file_exists($path)) {
            Log::info("Processing $path");
            $g = json_decode(file_get_contents($path));
            TaxonomyWhere::factory()->create(
                [
                    'name' => $g->properties->name->it,
                    // TODO: unique 'identifier' => Str::slug($g->properties->name->it,'-'),
                    'geometry' => DB::raw("ST_GeomFromGeoJSON('".json_encode($g->geometry)."')"),
                ]
            );
        } else {
            Log::info("Warning $path does not exists... SKIPPING!!");
        }
    }

    private function importPoiType($name, $identifier)
    {
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
    private function importTracks()
    {
        $path = base_path().'/tests/Fixtures/MontePisano/tracks.geojson';
        if (file_exists($path)) {
            Log::info('Processing TRACKS');
            $g = json_decode(file_get_contents($path));
            foreach ($g->features as $track) {
                $t = EcTrack::factory()->create([
                    'name' => isset($track->properties->name) ? $track->properties->name : 'ND',
                    'ref' => isset($track->properties->ref) ? $track->properties->ref : 'ND',
                    'geometry' => DB::raw("ST_Force3D(ST_GeomFromGeoJSON('".json_encode($track->geometry)."'))"),
                ]);
                $t->TaxonomyActivities()->attach($this->taxonomy_activity_cycling);
                $t->TaxonomyActivities()->attach($this->taxonomy_activity_hiking);
                $t->feature_image = $this->feature_image->id;
                $t->save();
            }
        } else {
            Log::info("Warning $path does not exists... SKIPPING!!");
        }

    }

    private function createAppsAndLayers()
    {
        // TODO: uncomment elbrus and webmapp after ELASTIC stuff
        // $app_elbrus = App::factory()->create(['api'=>'elbrus']);
        // $app_webmapp = App::factory()->create(['api'=>'webmapp']);
        $app = App::factory()->create(['api' => 'webapp']);
        Layer::factory()->create(['app_id' => $app->id]);
        Layer::factory()->create(['app_id' => $app->id]);

    }
}
