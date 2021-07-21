<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EcMedia extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('EcMedia')->insert([
            'name' => 'TestMedia1',
            'geometry' => DB::raw('ST_MakePoint(10, 45)'),
        ]);

        DB::table('EcMedia')->insert([
            'name' => 'TestMedia2',
            'geometry' => DB::raw('ST_MakePoint(10.0002, 45)'),
        ]);

        DB::table('EcMedia')->insert([
            'name' => 'TestMedia3',
            'geometry' => DB::raw('ST_MakePoint(10.0003, 45)'),
        ]);

        DB::table('EcTrack')->insert([
            'name' => 'TestTrack',
            'geometry' => DB::raw("(ST_GeomFromText('LINESTRING(10.0001 45 0, 10 46 0, 11 45, 11 47 0)'))"),
        ]);
    }
}
