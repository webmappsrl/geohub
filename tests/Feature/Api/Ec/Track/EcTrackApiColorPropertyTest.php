<?php

namespace Tests\Feature\Api\Ec\Track;

use Tests\TestCase;
use App\Models\EcTrack;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EcTrackApiColorPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     *
     * @return void
     * @test
     */

    public function ec_track_api_has_properties_key_test()
    {
        $track = EcTrack::factory()->create();
        $response = $this->get(route('api.ec.track.json', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('properties', $json);
    }

    /**
     * 
     * @return void
     * @test
     */

    public function ec_track_api_has_geometry_key_test()
    {
        $track = EcTrack::factory()->create();
        $response = $this->get(route('api.ec.track.json', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('geometry', $json);
    }

    /**
     * 
     * @return void
     * @test
     */

    public function ec_track_api_has_color_field_test()
    {
        $track = EcTrack::factory()->create(['color' => '#000000']);
        $response = $this->get(route('api.ec.track.json', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('track_color', $json['properties']);
    }

    /**
     * 
     * @return void
     * @test
     */

    public function ec_track_api_has_not_color_field_if_null_test()
    {
        $track = EcTrack::factory()->create(['color' => null]);
        $response = $this->get(route('api.ec.track.json', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayNotHasKey('track_color', $json['properties']);
    }

    /**
     * 
     * @return void
     * @test
     */

    public function ec_track_api_has_not_color_field_if_empty_test()
    {
        $track = EcTrack::factory()->create(['color' => '']);
        $response = $this->get(route('api.ec.track.json', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayNotHasKey('track_color', $json['properties']);
    }

    /**
     * 
     * @return void
     * @test
     */

    public function ec_track_api_has_not_color_field_if_not_set()
    {
        $track = EcTrack::factory()->create();
        $response = $this->get(route('api.ec.track.json', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayNotHasKey('track_color', $json['properties']);
    }
}
