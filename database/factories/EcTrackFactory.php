<?php

namespace Database\Factories;

use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class EcTrackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EcTrack::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $lat1 = $this->faker->randomFloat(2, 11, 13);
        $lat2 = $this->faker->randomFloat(2, 11, 13);
        $lng1 = $this->faker->randomFloat(2, 42, 45);
        $lng2 = $this->faker->randomFloat(2, 42, 45);
        return [
            'name' => $this->faker->name(),
            // TODO: 'osm_id' => $this->faker->name(),
            'excerpt' => $this->faker->text(90),
            'description' => $this->faker->text(),
            'source_id' => $this->faker->randomDigit(),
            'source' => $this->faker->text(100),
            'user_id' => User::all()->random()->id,
            'import_method' => $this->faker->name(),
            'geometry' => DB::raw("(ST_GeomFromText('LINESTRING($lat1 $lng1 0, $lat2 $lng1 0, $lat2 $lng2 0, $lat1 $lng2 0)'))"),
            'distance' => $this->faker->randomFloat(1, 10, 25),
            'ascent' => $this->faker->randomFloat(0, 300, 1000),
            'descent' => $this->faker->randomFloat(0, 300, 1000),
            'ele_min' => $this->faker->randomFloat(0, 0, 3000),
            'ele_max' => $this->faker->randomFloat(0, 3000, 5000),
            'ele_from' => $this->faker->randomFloat(0, 0, 3000),
            'ele_to' => $this->faker->randomFloat(0, 3000, 5000),
            'duration_forward' => $this->faker->randomFloat(0, 30, 300),
            'duration_backward' => $this->faker->randomFloat(0, 30, 300),
        ];
    }
}
