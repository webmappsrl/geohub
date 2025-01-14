<?php

namespace Database\Seeders;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use App\Models\User;
use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StelvioSeeder extends Seeder
{
    /**
     * The current Faker instance.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Create a new seeder instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->faker = $this->withFaker();
    }

    /**
     * Get a new Faker instance.
     *
     * @return \Faker\Generator
     */
    protected function withFaker()
    {
        return Container::getInstance()->make(Generator::class);
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sku = 'it.webmapp.pnstelvio';
        $users = User::factory(10)->create(['sku' => $sku]);
        foreach ($users as $user) {
            // 10 UGC POIS
            $ugc = UgcPoi::factory(10)->create(
                [
                    'user_id' => $user->id,
                    'sku' => $sku,
                    'geometry' => $this->getPoiGeometry(),
                ]
            );
            // 10 UGC MEDIA
            $ugc = UgcMedia::factory(10)->create(
                [
                    'user_id' => $user->id,
                    'sku' => $sku,
                    'geometry' => $this->getPoiGeometry(),
                ]
            );
            // 1 TRACK
            $ugc = UgcTrack::factory()->create(
                [
                    'user_id' => $user->id,
                    'sku' => $sku,
                    'geometry' => $this->getTrackGeometry(),
                ]
            );
        }
    }

    private function getPoiGeometry()
    {
        // [10.2858,46.0319,10.6344,46.5595]
        $lon = $this->faker->randomFloat(5, 10.2858, 10.6344);
        $lat = $this->faker->randomFloat(5, 46.0319, 46.5595);
        $geometry = DB::raw("(ST_GeomFromText('POINT($lon $lat)'))");

        return $geometry;
    }

    private function getTrackGeometry()
    {
        // [10.2858,46.0319,10.6344,46.5595]
        $line = [];
        for ($i = 0; $i < 20; $i++) {
            $lon = $this->faker->randomFloat(5, 10.2858, 10.6344);
            $lat = $this->faker->randomFloat(5, 46.0319, 46.5595);
            $line[$lat * 100000] = "$lon $lat";
        }
        ksort($line);
        $linestring = implode(',', $line);
        $geometry = DB::raw("(ST_GeomFromText('LINESTRING($linestring)'))");

        return $geometry;
    }
}
