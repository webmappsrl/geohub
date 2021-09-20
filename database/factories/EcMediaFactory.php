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
        Storage::disk('public')->put('/ec_media_test/test_108x137.jpg', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test_108x137.jpg'));
        Storage::disk('public')->put('/ec_media_test/test_108x148.jpg', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test_108x148.jpg'));
        Storage::disk('public')->put('/ec_media_test/test_100x200.jpg', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test_100x200.jpg'));
        Storage::disk('public')->put('/ec_media_test/test.jpg', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test.jpg'));

        $url108x137 = Storage::disk('public')->url('/ec_media_test/test_108x137.jpg');
        $url108x148 = Storage::disk('public')->url('/ec_media_test/test_108x148.jpg');
        $url100x200 = Storage::disk('public')->url('/ec_media_test/test_100x200.jpg');
        $url = Storage::disk('public')->url('/ec_media_test/test.jpg');

        $result = [
            'name' => [
                'it' => $this->faker->name(),
                'en' => $this->faker->name(),
            ],
            'description' => [
                'it' => $this->faker->text(),
                'en' => $this->faker->text(),
            ],
            'excerpt' => $this->faker->text(90),
            'source_id' => $this->faker->randomDigit(),
            'source' => $this->faker->text(100),
            'user_id' => User::all()->random()->id,
            'import_method' => $this->faker->name(),
            'url' => '/ec_media_test/test.jpg',
            'geometry' => DB::raw('ST_MakePoint(10, 45)'),
        ];

        $result['thumbnails'] = json_encode([
            '108x137' => $url108x137,
            '108x148' => $url108x148,
            '100x200' => $url100x200,
            'original' => $url
        ]);

        return $result;
    }

    public function frontEndSizes() {
        return $this->state(function (array $result) {
            Storage::disk('public')->put('/ec_media_test/test_1440x500.jpg', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test_1440x500.jpg'));
            Storage::disk('public')->put('/ec_media_test/test_400x200.jpg', file_get_contents(base_path() . '/tests/Fixtures/EcMedia/test_400x200.jpg'));

            $url1440x500 = Storage::disk('public')->url('/ec_media_test/test_1440x500.jpg');
            $url400x200 = Storage::disk('public')->url('/ec_media_test/test_400x200.jpg');

            $thumbnails = json_decode($result['thumbnails'],true);
            $thumbnails['1440x500'] = $url1440x500;
            $thumbnails['400x200'] = $url400x200;
            return [
                'thumbnails' => json_encode($thumbnails)
            ];

        });
    }
}
