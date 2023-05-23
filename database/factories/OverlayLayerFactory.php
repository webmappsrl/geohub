<?php

namespace Database\Factories;


use App\Models\OverlayLayer;
use Illuminate\Database\Eloquent\Factories\Factory;

class OverlayLayerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OverlayLayer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->numberBetween(1, 100),
            'name' => $this->faker->name(),
            'label' => $this->faker->name(),
            'icon' => $this->faker->name(),
            'feature_collection' => '',
            'app_id' => $this->faker->numberBetween(1, 40),
        ];
    }
}
