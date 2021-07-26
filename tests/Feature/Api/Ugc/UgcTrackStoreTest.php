<?php

namespace Tests\Feature\Ugc;

use App\Models\User;
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
        $this->actingAs($user, 'api');
        $geometry = [
            "type" => "LineString",
            "coordinates" => [[10, 44], [11, 44], [11, 43], [10, 43]]
        ];

        $data = [
            'user_id' => $user->id,
            'app_id' => 'it.webmapp.test',
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'geometry' => $geometry,
        ];

        $response = $this->postJson(route("api.ugc.track.store", $data));
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
    }

    /**
     * @test 
     * A basic feature test example.
     *
     * @return void
     */
    public function check_that_if_the_api_is_called_without_acting_as_the_user_it_responds_401()
    {
        $app_id = 'it.webmapp.test';
        $data = [
            'app_id' => $app_id,
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
        ];

        $response = $this->postJson(route("api.ugc.media.store", $data));
        $response->assertStatus(401);
    }
}
