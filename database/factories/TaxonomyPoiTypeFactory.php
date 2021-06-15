<?php

namespace Database\Factories;

use App\Models\TaxonomyPoiType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaxonomyPoiTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaxonomyPoiType::class;

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
        ];
    }
}
