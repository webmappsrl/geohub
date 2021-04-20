<?php

namespace Database\Factories;

use App\Models\UgcMedia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class UGCMediaFactory extends Factory {
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
    public function definition() {
        $geometry = null;
        if ($this->faker->numberBetween(1, 2) === 1)
            $geometry = DB::raw("(ST_GeomFromText('POINT(11 43)'))");
        else
            $geometry = DB::raw("(ST_GeomFromText('LINESTRING(11 43, 12 43, 12 44, 11 44)'))");

        return [
            'created_at' => $this->faker->dateTime('-1 month'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'app_id' => 'it.webmapp.' . strtolower($this->faker->word()),
            'relative_url' => 'media/images/ugc/' . $this->faker->firstName() . '.png',
            'geometry' => $geometry
        ];
    }
}
