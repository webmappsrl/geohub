<?php

namespace Database\Seeders;

use App\Models\TaxonomyActivity;
use Illuminate\Database\Seeder;

class TaxonomyActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TaxonomyActivity::factory()->create([
            'identifier' => 'hiking',
            'name' => 'Hiking',
        ]);
        TaxonomyActivity::factory()->create([
            'identifier' => 'cycling',
            'name' => 'Cycling',
        ]);
        TaxonomyActivity::factory()->create([
            'identifier' => 'running',
            'name' => 'Running',
        ]);
        TaxonomyActivity::factory()->create([
            'identifier' => 'walking',
            'name' => 'Walking',
        ]);
        TaxonomyActivity::factory()->create([
            'identifier' => 'skitouring',
            'name' => 'Skitouring',
        ]);
    }
}
