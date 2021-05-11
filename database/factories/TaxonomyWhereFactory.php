<?php

namespace Database\Factories;

use App\Models\TaxonomyWhere;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        return [
            'name' => $this->faker->name(),
            'source_id' => $this->faker->randomDigit,
            'admin_level' => $this->faker->randomDigit,
            'import_method' => $this->faker->name(),
            'geometry' => DB::raw("(ST_GeomFromText('MULTIPOLYGON(((11 43, 12 43, 12 44, 11 44, 11 43)))'))"),
        ];
    }
}
