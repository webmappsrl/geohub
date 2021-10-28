<?php

namespace Tests\Feature\Api\Ec\Track\Favorite;

use App\Models\EcTrack;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RemoveTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function test_api_works() {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $track = EcTrack::factory()->create();
        $id = $track->id;
        $response = $this->post("/api/ec/track/favorite/remove/$id");

        $response->assertStatus(200);
        $content = $response->json();
        $this->assertIsArray($content);
        $this->assertArrayHasKey('favorite', $content);
        $this->assertEquals(false, $content['favorite']);
    }

    public function test_double_remove_works() {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $track = EcTrack::factory()->create();
        $id = $track->id;
        $response = $this->post("/api/ec/track/favorite/remove/$id");
        $response->assertStatus(200);

        $response = $this->post("/api/ec/track/favorite/remove/$id");
        $response->assertStatus(200);
        $content = $response->json();
        $this->assertIsArray($content);
        $this->assertArrayHasKey('favorite', $content);
        $this->assertEquals(false, $content['favorite']);
    }

    public function test_without_authentication() {
        $track = EcTrack::factory()->create();
        $id = $track->id;
        $response = $this->post("/api/ec/track/favorite/remove/$id");
        $response->assertStatus(401);
    }

    public function test_with_invalid_track_id() {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $response = $this->post("/api/ec/track/favorite/remove/10");
        $response->assertStatus(404);
    }
}
