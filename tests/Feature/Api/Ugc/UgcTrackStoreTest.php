<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\App;
use App\Models\UgcMedia;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UgcTrackStoreTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUgcTrackStore()
    {
        $user = User::where('email', '=', 'team@webmapp.it')->first();
        App::factory([
            'app_id' => 'it.webmapp.test',
        ])->create();
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->andReturn(201);
        });

        $this->actingAs($user, 'api');
        $geometry = [
            'type' => 'LineString',
            'coordinates' => [[10, 44], [11, 44], [11, 43], [10, 43]],
        ];

        $data = [
            'type' => 'Feature',
            'properties' => [
                'user_id' => $user->id,
                'app_id' => 'it.webmapp.test',
                'name' => $this->faker->name(),
                'description' => $this->faker->text(),
            ],
            'geometry' => $geometry,
        ];

        $response = $this->postJson(route('api.ugc.track.store', $data));
        $content = $response->getContent();
        $response->assertStatus(201);
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('id', $json);
    }

    /**
     * @test
     *
     * @return void
     */
    public function check_that_if_the_api_is_called_without_access_token_it_responds_401()
    {
        $app_id = 'it.webmapp.test';
        $data = [
            'app_id' => $app_id,
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
        ];

        $response = $this->postJson(route('api.ugc.track.store', $data));
        $response->assertStatus(401);
    }

    public function test_track_upload_with_some_media()
    {
        $user = User::where('email', '=', 'team@webmapp.it')->first();
        App::factory([
            'app_id' => 'it.webmapp.test',
        ])->create();
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->atLeast(2)
                ->andReturn(201);
        });
        $medias = UgcMedia::factory(2)->create();

        $this->actingAs($user, 'api');
        $geometry = [
            'type' => 'LineString',
            'coordinates' => [[10, 44], [11, 44], [11, 43], [10, 43]],
        ];

        $data = [
            'type' => 'Feature',
            'properties' => [
                'user_id' => $user->id,
                'app_id' => 'it.webmapp.test',
                'name' => $this->faker->name(),
                'image_gallery' => $medias->pluck('id')->toArray(),
            ],
            'geometry' => $geometry,
        ];

        $response = $this->postJson(route('api.ugc.track.store', $data));
        $content = $response->getContent();
        $response->assertStatus(201);
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('id', $json);
    }
}
