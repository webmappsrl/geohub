<?php

namespace Database\Factories;

use App\Models\UgcTrack;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class UgcTrackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UgcTrack::class;

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

        $geometry = DB::raw("(ST_GeomFromText('LINESTRING(11 43, 12 43, 12 44, 11 44)'))");

        $rawData = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 10); $i++) {
            $rawData[strtolower($this->faker->word())] = implode(' ', $this->faker->words($this->faker->randomDigit()));
        }

        return [
            'name' => $this->faker->name(),
            'created_at' => $this->faker->dateTime('-1 month'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'sku' => 'it.webmapp.'.strtolower($this->faker->word()),
            'geometry' => $geometry,
            'raw_data' => json_encode($rawData),
            'user_id' => User::all()->random(1)->first()->id,
        ];
    }
}
