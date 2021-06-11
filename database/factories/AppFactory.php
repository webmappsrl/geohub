<?php

namespace Database\Factories;

use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = App::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'app_id' => 'it.webmapp.'.$this->faker->unique()->slug(),
            'customerName' => $this->faker->name(),
            'user_id' => \App\Models\User::all()->random()->id,
        ];
    }
}
