<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\EcPoi;
use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppElbrusEcTrackGeojsonTest extends TestCase
{
    use RefreshDatabase;

    public function testNoAppAndNoTrackReturns404() {
        $result = $this->getJson('/api/app/elbrus/0/geojson/ec_track_0.geojson', []);
        $this->assertEquals(404, $result->getStatusCode());

    }

    public function testAppAndNoTrackReturns404() {
        $app=App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/geojson/ec_track_0.geojson', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testNoAppTrackReturns404() {
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/0/geojson/ec_track_'.$track->id.'.geojson', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testAppAndTrackReturns200() {
        $app=App::factory()->create();
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/geojson/ec_track_'.$track->id.'.geojson', []);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testMappingUnderscoreAndColon() {
        $app=App::factory()->create();
        $track = EcTrack::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/geojson/ec_track_'.$track->id.'.geojson', []);
        $this->assertEquals(200, $result->getStatusCode());

        // test response is geojson
        $geojson = json_decode($result->content(),true);
        $this->assertEquals('Feature',$geojson['type']);
        $this->assertTrue(isset($geojson['properties']));
        $this->assertTrue(isset($geojson['geometry']));

        // test fields with colon ":"
        // TO BE MAPPED: contact_phone, contact_email,
        $this->assertEquals($track->ele_from,$geojson['properties']['ele:from']);
        $this->assertEquals($track->ele_to,$geojson['properties']['ele:to']);
        $this->assertEquals($track->ele_min,$geojson['properties']['ele:min']);
        $this->assertEquals($track->ele_max,$geojson['properties']['ele:max']);
        $this->assertEquals($track->duration_forward,$geojson['properties']['duration:forward']);
        $this->assertEquals($track->duration_backward,$geojson['properties']['duration:backward']);

    }
}
