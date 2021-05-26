<?php

namespace Database\Factories;

use App\Models\EcMedia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\TaxonomyWhere;

class EcMediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EcMedia::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        Storage::disk('public')->put('/ec_media_test/test.jpg', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test.jpg'));

        return [
            'name' => $this->faker->name(),
            'excerpt' => $this->faker->text(90),
            'description' => $this->faker->text(),
            'source_id' => $this->faker->randomDigit(),
            'source' => $this->faker->text(100),
            'user_id' => User::all()->random()->id,
            'import_method' => $this->faker->name(),
            'url' => '/ec_media_test/test.jpg',
            'geometry' => DB::raw('ST_MakePoint(10, 45)'),
        ];
    }
}
