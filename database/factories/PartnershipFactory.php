<?php

namespace Database\Factories;

use App\Models\Partnership;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'short_name' => $this->faker->word(),
            'validator' => $this->faker->word(),
        ];
    }
}
