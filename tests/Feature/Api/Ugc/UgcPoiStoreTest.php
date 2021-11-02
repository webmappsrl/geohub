<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\App;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UgcPoiStoreTest extends TestCase {
    use RefreshDatabase, WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUgcPoiStore() {
        $user = User::where('email', '=', 'team@webmapp.it')->first();
        App::factory([
            'app_id' => 'it.webmapp.test'
        ])->create();
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->andReturn(201);
        });
        $this->actingAs($user, 'api');
        $geometry = [
            "type" => "Point",
            "coordinates" => [10, 44]
        ];

        $data = [
            'type' => 'Feature',
            'properties' => [
                'app_id' => 'it.webmapp.test',
                'name' => $this->faker->name(),
                'description' => $this->faker->text()
            ],
            'geometry' => $geometry
        ];

        $response = $this->post(route("api.ugc.poi.store"), $data);
        $content = $response->getContent();
        $response->assertStatus(201);
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('id', $json);
    }

    /**
     * @test
     */
    public function check_that_if_the_api_is_called_without_access_token_it_responds_401() {
        $app_id = 'it.webmapp.test';
        $data = [
            'app_id' => $app_id,
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
        ];

        $response = $this->post(route("api.ugc.poi.store", $data));
        $response->assertStatus(401);
    }
}
