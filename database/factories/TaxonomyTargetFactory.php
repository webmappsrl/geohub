<?php

namespace Database\Factories;

use App\Models\TaxonomyTarget;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaxonomyTargetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaxonomyTarget::class;

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
            'source_id' => $this->faker->randomDigit,
            'source' => $this->faker->text(100),
            'admin_level' => $this->faker->randomDigit,
            'import_method' => $this->faker->name(),
        ];
    }
}
