<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Layer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class LayerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Layer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [

            // MAIN
            'app_id' => App::factory(),
            'name' => $this->faker->name(),
            'title' => $this->faker->sentence(5),
            'subtitle' => $this->faker->sentence(10),
            'description' => $this->faker->sentence(100),
            // TODO: '' => $this->faker->,

            // BEHAVIOUR
            'noDetails' => $this->faker->boolean(),
            'noInteraction' => $this->faker->boolean(),
            'minZoom' => $this->faker->numberBetween(9,12),
            'maxZoom' => $this->faker->numberBetween(16,19),
            'preventFilter' => $this->faker->boolean(),
            'invertPolygons' => $this->faker->boolean(),
            'alert' => $this->faker->boolean(),
            'show_label' => $this->faker->boolean(),
            // TODO: parameters '' => $this->faker->,

            // STYLE
            'color' => $this->faker->hexColor(),
            'fill_color' => $this->faker->hexColor(),
            'fill_opacity' => $this->faker->numberBetween(1,100),
            'stroke_width' => $this->faker->numberBetween(1,5),
            'stroke_opacity' => $this->faker->numberBetween(1,100),
            'zindex' => $this->faker->numberBetween(1,99),
            // TODO: line_dash '' => $this->faker->,

            // DATA
            'data_use_bbox' => $this->faker->boolean(),
            'data_use_only_my_data' => $this->faker->boolean(),
        ];
    }
}
