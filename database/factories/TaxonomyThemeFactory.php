<?php

namespace Database\Factories;

use App\Models\TaxonomyTheme;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxonomyThemeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaxonomyTheme::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->name();
        return [
            'name' => $name,
            'excerpt' => $this->faker->text(90),
            'description' => $this->faker->text(),
            'identifier' => $name,
            'source_id' => $this->faker->randomDigit(),
            'source' => $this->faker->text(100),
            'import_method' => $this->faker->name(),
            'color' => $this->faker->hexColor(),
            'stroke_width' => $this->faker->randomFloat(1, 0.5, 10),
            'stroke_opacity' => $this->faker->randomFloat(2, 0, 1),
            'line_dash' => '2.0, 4, 7.2, 7.9, 1.1',
            'min_visible_zoom' => $this->faker->randomFloat(0, 5, 19),
            'min_size_zoom' => $this->faker->randomFloat(0, 5, 19),
            'min_size' => $this->faker->randomFloat(1, 0.1, 4),
            'max_size' => $this->faker->randomFloat(1, 0.1, 4),
            'icon_zoom' => $this->faker->randomFloat(0, 5, 19),
            'icon_size' => $this->faker->randomFloat(1, 0.1, 4),
        ];
    }
}
