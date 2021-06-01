<?php

namespace Database\Factories;

use App\Models\EcPoi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class EcPoiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EcPoi::class;

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
            'user_id' => User::all()->random()->id,
            'geometry' => DB::raw("(ST_GeomFromText('POINT(10.43 43.70)'))"),
        ];
    }
}
