<?php

namespace Database\Factories;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class EcPoiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EcPoi::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => [
                'it' => $this->faker->name(),
                'en' => $this->faker->name(),
            ],
            'description' => [
                'it' => $this->faker->text(),
                'en' => $this->faker->text(),
            ],
            'excerpt' => [
                'it' => $this->faker->text(90),
                'en' => $this->faker->text(90),
            ],
            'user_id' => User::all()->random()->id,
            'geometry' => DB::raw("(ST_GeomFromText('POINT(10.43 43.70)'))"),
            'related_url' => $this->faker->url().';'.$this->faker->url(),
            'contact_phone' => $this->faker->phoneNumber(),
            'contact_email' => $this->faker->safeEmail(),
            'addr_street' => $this->faker->streetAddress(),
            'addr_housenumber' => $this->faker->numberBetween(11,99),
            'addr_postcode' => $this->faker->postcode(),
            'addr_locality' => $this->faker->city(),
            'opening_hours' => '24/7',
            'ele' => $this->faker->randomNumber(3),
            'feature_image' => EcMedia::factory(),
        ];
    }
}
