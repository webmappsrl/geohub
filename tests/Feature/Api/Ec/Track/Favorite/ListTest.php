<?php

namespace Tests\Feature\Api\Ec\Track\Favorite;

use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTest extends TestCase {
    use RefreshDatabase;

    public function test_api_works_with_empty_list() {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $response = $this->post("/api/ec/track/favorite/list");

        $response->assertStatus(200);
        $content = $response->json();
        $this->assertIsArray($content);
        $this->assertArrayHasKey('favorites', $content);
        $this->assertIsArray($content['favorites']);
        $this->assertCount(0, $content['favorites']);
    }

    public function test_list_with_some_elements() {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $tracks = EcTrack::factory(2)->create();
        $id = $tracks[0]->id;
        $response = $this->post("/api/ec/track/favorite/add/$id");
        $response->assertStatus(200);

        $id = $tracks[1]->id;
        $response = $this->post("/api/ec/track/favorite/add/$id");
        $response->assertStatus(200);

        $response = $this->post("/api/ec/track/favorite/list");
        $response->assertStatus(200);
        $content = $response->json();
        $this->assertIsArray($content);
        $this->assertArrayHasKey('favorites', $content);
        $this->assertIsArray($content['favorites']);
        $this->assertCount(2, $content['favorites']);
        $this->assertTrue(in_array($tracks[0]->id, $content['favorites']));
        $this->assertTrue(in_array($tracks[1]->id, $content['favorites']));
    }

    public function test_without_authentication() {
        $response = $this->post("/api/ec/track/favorite/list");
        $response->assertStatus(401);
    }

    public function test_with_invalid_track_id() {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $response = $this->post("/api/ec/track/favorite/toggle/10");
        $response->assertStatus(404);
    }
}
