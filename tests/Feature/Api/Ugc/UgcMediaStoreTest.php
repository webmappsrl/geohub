<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UgcMediaStoreTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * @test 
     * A basic feature test example.
     *
     * @return void
     */
    public function check_that_call_api_to_store_ugcmedia_for_authenticated_user()
    {
        $user = User::where('email', '=', 'team@webmapp.it')->first();
        $this->actingAs($user, 'api');
        $geometry = [
            "type" => "Point",
            "coordinates" => [10, 44]
        ];

        $image = base_path() . '/tests/Fixtures/EcMedia/test.jpg';
        $content = file_get_contents($image);
        $exif = exif_read_data($image);

        $app_id = 'it.webmapp.test';
        $data = [
            'app_id' => $app_id,
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'geometry' => $geometry,
            'image' => base64_encode($content),
            'raw_data' => json_encode($exif),
        ];

        $response = $this->postJson(route("api.ugc.media.store", $data));
        Storage::disk('public')->delete('ugc_media/' . $app_id . '/1');
        $content = $response->getContent();
        $response->assertStatus(201);
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        $this->assertIsInt($json['data']['id']);
        $this->assertEquals($user->id, $json['data']['user_id']);
        $this->assertEquals($data['app_id'], $json['data']['app_id']);
        $this->assertEquals($data['name'], $json['data']['name']);
        $this->assertEquals($data['description'], $json['data']['description']);
        $this->assertEquals($data['geometry'], $json['data']['geometry']);
        $this->assertArrayHasKey('relative_url', $json['data']);
    }

    /**
     * @test 
     * A basic feature test example.
     *
     * @return void
     */
    public function check_that_if_the_api_is_called_without_access_token_it_responds_401()
    {
        $app_id = 'it.webmapp.test';
        $data = [
            'app_id' => $app_id,
            'description' => $this->faker->text(),
        ];

        $response = $this->postJson(route("api.ugc.media.store", $data));
        $response->assertStatus(401);
    }
}
