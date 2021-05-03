<?php

namespace Database\Factories;

use App\Models\UgcPoi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class UgcPoiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UgcPoi::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $geometry = DB::raw("(ST_GeomFromText('POINT(11 43)'))");

        $rawData = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 10); $i++) {
            $rawData[strtolower($this->faker->word())] = implode(" ", $this->faker->words($this->faker->randomDigit()));
        }

        return [
            'created_at' => $this->faker->dateTime('-1 month'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'app_id' => 'it.webmapp.' . strtolower($this->faker->word()),
            'name' => $this->faker->firstName(),
            'geometry' => $geometry,
            'raw_data' => json_encode($rawData)
        ];
    }
}
