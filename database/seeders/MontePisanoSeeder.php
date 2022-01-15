<?php

namespace Database\Seeders;

use App\Models\TaxonomyWhere;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MontePisanoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Import WHERE

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
                    'name' => $g->properties->name,
                    'geometry' => DB::raw("ST_GeomFromGeoJSON('".json_encode($g->geometry)."')"),
                ]
            );
        }
        else {
            Log::info("Warning $path does not exists... SKIPPING!!");
        }
    }
}
