<?php

namespace Database\Factories;

use App\Models\UserGeneratedData;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserGeneratedDataFactory extends Factory
{
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
    public function definition()
    {
        $rawData = [];
        for ($i = 0; $i < $this->faker->numberBetween(0, 10); $i++) {
            $rawData[strtolower($this->faker->word())] = implode(" ", $this->faker->words($this->faker->randomDigit));
        }

        $rawGallery = [];
        for ($i = 0; $i < $this->faker->numberBetween(0, 5); $i++) {
            $rawGallery[] = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAARElEQVR42u3PMREAAAgEIE1u9DeDqwcN6EqmHmgRERERERERERERERERERERERERERERERERERERERERERERERERkYsFOoB8nTpF298AAAAASUVORK5CYII=";
        }

        return [
            'created_at' => $this->faker->dateTime('-1month'),
            'updated_at' => $this->faker->dateTimeBetween('-1month', 'now'),
            'app_id' => 'it.webmapp.' . strtolower($this->faker->word),
            'raw_data' => json_encode($rawData),
            'raw_gallery' => json_encode($rawGallery),
        ];
    }
}
