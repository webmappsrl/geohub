<?php

namespace Tests\Feature;

use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TrackSharePageTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function when_user_access_to_track_with_not_existing_id_then_it_returns_404()
    {
        $track = EcTrack::factory()->create();
        while( in_array( ($n = mt_rand(1,100)), array($track->id) ) );
        $response = $this->get('/track/'.$n);
        $response->assertStatus(404);
    }
    /**
     * @test
     */
    public function when_user_access_to_track_with_existing_id_then_it_returns_200()
    {
        $track = EcTrack::factory()->create();
        $response = $this->get('/track/'.$track->id);
        $response->assertStatus(200);
    }
    /**
     * @test
     */
    public function when_user_access_to_track_check_the_title_is_correct()
    {
        $track = EcTrack::factory()->create();
        $response = $this->get('/track/'.$track->id);
        $response->assertSee($track->name);
    }
}
