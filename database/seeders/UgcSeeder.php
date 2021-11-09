<?php

namespace Database\Seeders;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use Illuminate\Database\Seeder;

class UgcSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // User

      UgcPoi::factory(100)->create();
      UgcTrack::factory(100)->create();
      UgcMedia::factory(100)->create();
    }
}
