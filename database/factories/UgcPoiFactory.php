<?php

namespace Database\Factories;

use App\Models\UgcPoi;
use App\Models\User;
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
        if(User::count()==0) {
            User::factory()->create(100);
        }
        /** @var  $geometry  LON : 10.3 - 10.7 LAT : 43.6 - 43.9 */
        $lon = $this->faker->randomFloat(5,10.3,10.7);
        $lat = $this->faker->randomFloat(5,43.6,43.9);
        $geometry = DB::raw("(ST_GeomFromText('POINT($lon $lat)'))");

        $rawData = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 10); $i++) {
            $rawData[strtolower($this->faker->word())] = implode(" ", $this->faker->words($this->faker->randomDigit()));
        }

        return [
            'name' => $this->faker->name(),
            'app_id' => 'it.webmapp.' . strtolower($this->faker->word()),
            'geometry' => $geometry,
            'raw_data' => json_encode($rawData),
            'user_id' => User::all()->random(1)->first()->id,
        ];
    }
}
