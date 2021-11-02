<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\UgcMedia;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UgcMediaStoreTest extends TestCase {
    use RefreshDatabase, WithFaker;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    /**
     * @test
     * A basic feature test example.
     *
     * @return void
     */
    public function check_that_call_api_to_store_ugc_media_for_authenticated_user() {
        $user = User::where('email', '=', 'team@webmapp.it')->first();
        $this->actingAs($user, 'api');
        $geometry = [
            "type" => "Point",
            "coordinates" => [10, 44]
        ];

        $image = base_path() . '/tests/Fixtures/EcMedia/test.jpg';
        $exif = exif_read_data($image);
        $uploadedFile = new UploadedFile(
            $image,
            'large-avatar.jpg',
            'image/jpeg',
            null,
            true
        );

        $app_id = 'it.webmapp.test';
        $geojson = [
            'type' => 'Feature',
            'properties' => [
                'app_id' => $app_id,
                'name' => $this->faker->name(),
                'description' => $this->faker->text(),
                'raw_data' => json_encode($exif),
            ],
            'geometry' => $geometry
        ];

        $response = $this->postJson(route("api.ugc.media.store"), [
            'geojson' => json_encode($geojson),
            'image' => $uploadedFile
        ]);
        $content = $response->getContent();
        $response->assertStatus(201);
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('id', $json);
        $this->assertIsInt($json['id']);

        $media = UgcMedia::find($json['id']);
        $this->assertTrue(isset($media));
        $this->assertTrue(file_exists(Storage::disk('public')->path($media->relative_url)));

        Storage::disk('public')->delete($media->relative_url);
    }

    /**
     * @test
     */
    public function check_that_if_the_api_is_called_without_access_token_it_responds_401() {
        $app_id = 'it.webmapp.test';
        $data = [
            'app_id' => $app_id,
            'description' => $this->faker->text(),
        ];

        $response = $this->postJson(route("api.ugc.media.store"), $data);
        $response->assertStatus(401);
    }

    public function test_for_authenticated_user_with_missing_name_and_description() {
        $user = User::where('email', '=', 'team@webmapp.it')->first();
        $this->actingAs($user, 'api');
        $geometry = [
            "type" => "Point",
            "coordinates" => [10, 44]
        ];

        $image = base_path() . '/tests/Fixtures/EcMedia/test.jpg';
        $exif = exif_read_data($image);
        $uploadedFile = new UploadedFile(
            $image,
            'large-avatar.jpg',
            'image/jpeg',
            null,
            true
        );

        $app_id = 'it.webmapp.test';
        $geojson = [
            'type' => 'Feature',
            'properties' => [
                'app_id' => $app_id,
                'raw_data' => json_encode($exif),
            ],
            'geometry' => $geometry
        ];

        $response = $this->postJson(route("api.ugc.media.store"), [
            'geojson' => json_encode($geojson),
            'image' => $uploadedFile
        ]);
        $content = $response->getContent();
        $response->assertStatus(201);
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('id', $json);
        $this->assertIsInt($json['id']);

        $media = UgcMedia::find($json['id']);
        $this->assertTrue(isset($media));
        $this->assertTrue(file_exists(Storage::disk('public')->path($media->relative_url)));

        Storage::disk('public')->delete($media->relative_url);
    }
}
