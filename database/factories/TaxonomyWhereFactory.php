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
            'name' => $name,
            'excerpt' => $this->faker->text(90),
            'description' => $this->faker->text(),
            'identifier' => $name,
            'source' => $this->faker->text(100),
            'source_id' => $this->faker->randomDigit(),
            'admin_level' => $this->faker->randomDigit(),
            'import_method' => $this->faker->name(),
            'geometry' => DB::raw("(ST_GeomFromText('MULTIPOLYGON(((10 45, 11 45, 11 46, 11 46, 10 45)))'))"),
        ];
    }
}
