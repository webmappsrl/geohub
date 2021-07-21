<?php

namespace Database\Seeders;

use App\Models\EcMedia;
use App\Models\EcTrack;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EcMediaAndTrack extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $media1 = EcMedia::factory()->create([
            'name' => 'TestMedia1',
            'geometry' => DB::raw('ST_MakePoint(10, 45)'),
            'url' => '/ec_media_test/test.jpg',
        ]);

        $media2 = EcMedia::factory()->create([
            'name' => 'TestMedia2',
            'geometry' => DB::raw('ST_MakePoint(10.0002, 45)'),
            'url' => '/ec_media_test/test.jpg',
        ]);

        $media3 = EcMedia::factory()->create([
            'name' => 'TestMedia3',
            'geometry' => DB::raw('ST_MakePoint(10.0003, 46)'),
            'url' => '/ec_media_test/test.jpg',
        ]);

        $media3 = EcTrack::factory()->create([
            'name' => 'TestTrack',
            'geometry' => DB::raw("(ST_GeomFromText('LINESTRING(10.0001 45 0,  10 46 0,  11 45 0, 11 47 0)'))"),
        ]);
    }
}
