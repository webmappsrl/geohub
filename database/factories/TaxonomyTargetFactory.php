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
            'excerpt' => $this->faker->Str::random(10),
            'source_id' => $this->faker->randomDigit,
            'source' => Str::random(10),
            'admin_level' => $this->faker->randomDigit,
            'import_method' => Str::random(10),
        ];
    }
}
