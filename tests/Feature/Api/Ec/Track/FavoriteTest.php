<?php

namespace Tests\Feature\Api\Ec\Track;

use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase {
    use RefreshDatabase;

    public function test_api_works() {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $track = EcTrack::factory()->create();
        $id = $track->id;
        $response = $this->post("/api/ec/track/$id/favorite");

        $response->assertStatus(200);
        $content = $response->json();
        $this->assertIsArray($content);
        $this->assertArrayHasKey('favorite', $content);
        $this->assertEquals(true, $content['favorite']);
    }

    public function test_double_toggle_works() {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $track = EcTrack::factory()->create();
        $id = $track->id;
        $response = $this->post("/api/ec/track/$id/favorite");
        $response->assertStatus(200);

        $response = $this->post("/api/ec/track/$id/favorite");
        $response->assertStatus(200);
        $content = $response->json();
        $this->assertIsArray($content);
        $this->assertArrayHasKey('favorite', $content);
        $this->assertEquals(false, $content['favorite']);
    }

    public function test_without_authentication() {
        $track = EcTrack::factory()->create();
        $id = $track->id;
        $response = $this->post("/api/ec/track/$id/favorite");
        $response->assertStatus(401);
    }

    public function test_with_invalid_track_id() {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $response = $this->post("/api/ec/track/10/favorite");
        $response->assertStatus(404);
    }
}
