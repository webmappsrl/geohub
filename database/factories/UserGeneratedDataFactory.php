<?php

namespace Database\Factories;

use App\Models\UserGeneratedData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class UserGeneratedDataFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserGeneratedData::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        $rawData = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 10); $i++) {
            $rawData[strtolower($this->faker->word())] = implode(" ", $this->faker->words($this->faker->randomDigit()));
        }
        $rawData['name'] = implode(" ", $this->faker->words($this->faker->randomDigit()));

        $rawGallery = [];
        for ($i = 0; $i < $this->faker->numberBetween(0, 5); $i++) {
            $rawGallery[] = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAARElEQVR42u3PMREAAAgEIE1u9DeDqwcN6EqmHmgRERERERERERERERERERERERERERERERERERERERERERERERERkYsFOoB8nTpF298AAAAASUVORK5CYII=";
        }

        $geometry = null;
        if ($this->faker->numberBetween(1, 2) === 1)
            $geometry = DB::raw("(ST_GeomFromText('POINT(11 43)'))");
        else
            $geometry = DB::raw("(ST_GeomFromText('LINESTRING(11 43, 12 43, 12 44, 11 44)'))");

        return [
            'created_at' => $this->faker->dateTime('-1month'),
            'updated_at' => $this->faker->dateTimeBetween('-1month', 'now'),
            'app_id' => 'it.webmapp.' . strtolower($this->faker->word()),
            'raw_data' => json_encode($rawData),
            'raw_gallery' => json_encode($rawGallery),
            'name' => $rawData['name'],
            'geometry' => $geometry
        ];
    }
}
