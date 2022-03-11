<?php

namespace Tests\Feature;

use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EcTrackApiKmlHasNoDescriptionTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Undocumented function
     * @test
     * @return void
     */
    public function kml_api_has_no_description_tag()
    {
        $track = EcTrack::factory()->create();
        $response = $this->get(route('api.ec.track.download.kml', ['id' => $track->id]));
        $response->assertStatus(200);
        $content = $response->content();
        $xml = simplexml_load_string($content);
        $this->assertFalse(isset($xml->Placemark->description));
    }
}
