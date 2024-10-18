<?php

namespace Database\Factories;

use App\Models\UgcMedia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class UgcMediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UgcMedia::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        if (User::count() == 0) {
            User::factory()->create(100);
        }

        return [
            'name' => $this->faker->name(),
            'created_at' => $this->faker->dateTime('-1 month'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'sku' => 'it.webmapp.' . strtolower($this->faker->word()),
            'relative_url' => 'media/images/ugc/' . $this->faker->firstName() . '.png',
            'geometry' => DB::raw("(ST_GeomFromText('POINT(11 43)'))"),
            'user_id' => User::all()->random(1)->first()->id,
        ];
    }
}
