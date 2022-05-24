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