<?php

namespace Database\Factories;

use App\Models\OutSourceFeature;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;


class OutSourcePoiFactory extends Factory {

    protected $model = OutSourceFeature::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // POI
        $lon = $this->faker->longitude(10,11);
        $lat = $this->faker->latitude(43,44);
        $geometry = DB::raw("(ST_GeomFromText('POINT($lon $lat)'))");

        return [
            'provider' => 'FactoryFake',
            'type' => 'poi',
            'source_id' => $this->faker->uuid(),
            'tags' => [
                'name' => [ 'it' => $this->faker->name() ],
                'description' => [ 'it' => $this->faker->text()],
            ],
            'geometry' => $geometry,
        ];
    }

}
class OutSourceTrackFactory extends Factory {
    
    protected $model = OutSourceFeature::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // TRACK
        $lon1 = $this->faker->randomFloat(2, 11, 13);
        $lon2 = $this->faker->randomFloat(2, 11, 13);
        $lat1 = $this->faker->randomFloat(2, 42, 45);
        $lat2 = $this->faker->randomFloat(2, 42, 45);
        $geometry = DB::raw("(ST_GeomFromText('LINESTRING($lon1 $lat2, $lon2 $lat1, $lon2 $lat2, $lon1 $lat2)'))");

        return [
            'provider' => 'FactoryFake',
            'type' => 'track',
            'source_id' => $this->faker->uuid(),
            'tags' => [
                'name' => [ 'it' => $this->faker->name() ],
                'description' => [ 'it' => $this->faker->text()],
            ],
            'geometry' => $geometry,
        ];
    }

}
