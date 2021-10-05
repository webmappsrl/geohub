<?php

namespace Tests\Feature\Api\Ec\Track\Geojson;

use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCanDownloadTest extends TestCase {
    use RefreshDatabase;

    public function test_guest_user_can_not_download() {
        $track = EcTrack::factory()->create();

        $response = $this->get(route('api.ec.track.view.geojson', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json);
        $this->assertArrayHasKey('properties', $json);
        $this->assertIsArray($json['properties']);
        $this->assertArrayHasKey('user_can_download', $json['properties']);
        $this->assertFalse($json['properties']['user_can_download']);
    }

    public function test_logged_user_can_not_download() {
        $track = EcTrack::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $response = $this->get(route('api.ec.track.view.geojson', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json);
        $this->assertArrayHasKey('properties', $json);
        $this->assertIsArray($json['properties']);
        $this->assertArrayHasKey('user_can_download', $json['properties']);
        $this->assertFalse($json['properties']['user_can_download']);
    }

    public function test_logged_user_can_download() {
        $track = EcTrack::factory()->create();
        $user = User::factory()->create();

        $user->downloadableEcTracks()->sync([$track->id]);

        $this->actingAs($user, 'api');

        $response = $this->get(route('api.ec.track.view.geojson', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json);
        $this->assertArrayHasKey('properties', $json);
        $this->assertIsArray($json['properties']);
        $this->assertArrayHasKey('user_can_download', $json['properties']);
        $this->assertTrue($json['properties']['user_can_download']);
    }
}
