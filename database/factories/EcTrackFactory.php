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
        return [
            'name' => $this->faker->name(),
            'excerpt' => $this->faker->text(90),
            'description' => $this->faker->text(),
            'source_id' => $this->faker->randomDigit(),
            'source' => $this->faker->text(100),
            'user_id' => User::all()->random()->id,
            'import_method' => $this->faker->name(),
            'geometry' => DB::raw("(ST_GeomFromText('LINESTRING(11 43, 12 43, 12 44, 11 44)'))"),
            'distance_comp' => $this->faker->randomFloat(),
        ];
    }
}
