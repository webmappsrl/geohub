<?php

namespace Database\Factories;

use App\Models\TaxonomyWhere;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class TaxonomyWhereFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaxonomyWhere::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->name();
        return [
            'name' => [
                'it' => $name,
                'en' => $name,
            ],
            'description' => [
                'it' => $this->faker->text(),
                'en' => $this->faker->text(),
            ],
            'excerpt' => [
                'it' => $this->faker->text(90),
                'en' => $this->faker->text(90),
            ],
            'identifier' => $name,
            'source' => $this->faker->text(100),
            'source_id' => $this->faker->randomDigit(),
            'admin_level' => $this->faker->randomDigit(),
            'import_method' => $this->faker->name(),
            'geometry' => DB::raw("(ST_GeomFromText('MULTIPOLYGON(((10 45, 11 45, 11 46, 11 46, 10 45)))'))"),
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
