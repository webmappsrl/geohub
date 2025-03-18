<?php

namespace Database\Factories;

use App\Models\OutSourceTrack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class OutSourceTrackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OutSourceTrack::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // TRACK
        $lon1 = $this->faker->randomFloat(2, 11, 13);
        $lon2 = $this->faker->randomFloat(2, 11, 13);
        $lat1 = $this->faker->randomFloat(2, 42, 45);
        $lat2 = $this->faker->randomFloat(2, 42, 45);
        $geometry = DB::raw("(ST_GeomFromText('LINESTRING($lon1 $lat2, $lon2 $lat1, $lon2 $lat2, $lon1 $lat2)'))");

        return [
            'provider' => 'FactoryFake',
            'type' => 'track',
            'source_id' => $this->faker->uuid(),
            'tags' => [
                'name' => ['it' => $this->faker->name()],
                'description' => ['it' => $this->faker->text()],
                'from' => $this->faker->city(),
                'to' => $this->faker->city(),
            ],
            'geometry' => $geometry,
        ];
    }
}
