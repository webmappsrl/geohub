<?php

namespace Database\Factories;

use App\Models\EcMedia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EcMediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EcMedia::class;

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
            'url' => $this->faker->text(100),
        ];
    }
}
