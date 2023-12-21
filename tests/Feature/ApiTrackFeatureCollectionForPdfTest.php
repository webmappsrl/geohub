<?php

namespace Tests\Feature;

use App\Models\EcPoi;
use App\Models\EcTrack;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiTrackFeatureCollectionForPdfTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test if the Api works
     *
     * @return void
     */
    public function test_api_works()
    {
        //create a track
        $track = EcTrack::factory()->create();

        //create a poi
        $poi = EcPoi::factory()->create();

        //associate poi to track
        $track->EcPois()->attach($poi);

        //call the api
        $response = $this->get(route('api.ec.track.feature_collection_for_pdf', ['id' => $track->id]));

        //check if the response is ok
        $response->assertStatus(200);
    }

    /**
     * Test if the Api returns a FeatureCollection
     *
     * @return void
     */
    public function test_api_returns_feature_collection()
    {
        //create a track
        $track = EcTrack::factory()->create();

        //create a poi
        $poi = EcPoi::factory()->create();

        //associate poi to track
        $track->EcPois()->attach($poi);

        //call the api
        $response = $this->get(route('api.ec.track.feature_collection_for_pdf', ['id' => $track->id]));

        //check if the response is a FeatureCollection
        $response->assertJsonStructure([
            'type',
            'features' => [
                '*' => [
                    'type',
                    'properties',
                    'geometry' => [
                        'type',
                        'coordinates',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test if the Api returns a FeatureCollection with the correct structure
     *
     * @return void
     */
    public function test_api_returns_feature_collection_with_correct_structure()
    {
        //create a track
        $track = EcTrack::factory()->create();

        //create a poi
        $poi = EcPoi::factory()->create();

        //associate poi to track
        $track->EcPois()->attach($poi);

        //call the api
        $response = $this->get(route('api.ec.track.feature_collection_for_pdf', ['id' => $track->id]));

        //check if the geojson has the correct keys
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('type', $json);
        $this->assertArrayHasKey('features', $json);
        $this->assertIsArray($json['features']);
        $this->assertArrayHasKey('type', $json['features'][0]);
        $this->assertArrayHasKey('properties', $json['features'][0]);
        $this->assertArrayHasKey('geometry', $json['features'][0]);
        $this->assertArrayHasKey('type', $json['features'][0]['geometry']);
        $this->assertArrayHasKey('coordinates', $json['features'][0]['geometry']);

        //check if the track ['properties'] has the correct keys
        $this->assertArrayHasKey('id', $json['features'][0]['properties']);
        $this->assertArrayHasKey('type_sisteco', $json['features'][0]['properties']);
        $this->assertArrayHasKey('strokeColor', $json['features'][0]['properties']);
        $this->assertArrayHasKey('fillColor', $json['features'][0]['properties']);

        //check if the poi ['properties'] has the correct keys
        $this->assertArrayHasKey('id', $json['features'][1]['properties']);
        $this->assertArrayHasKey('type_sisteco', $json['features'][1]['properties']);
        $this->assertArrayHasKey('pointRadius', $json['features'][1]['properties']);
        $this->assertArrayHasKey('pointFillColor', $json['features'][1]['properties']);
        $this->assertArrayHasKey('pointStrokeColor', $json['features'][1]['properties']);
    }

    /**
     * Test if the track is not found the Api returns a 404
     *
     * @return void
     */
    public function test_api_returns_404_if_track_not_found()
    {
        //call the api
        $response = $this->get(route('api.ec.track.feature_collection_for_pdf', ['id' => 1]));

        //check if the response is a 404
        $response->assertStatus(404);
    }

    /**
     * Test if the track has no related pois the geojson structure contains only track feature
     *
     * @return void
     */
    public function test_api_returns_feature_collection_with_only_track_feature_if_no_pois()
    {
        //create a track
        $track = EcTrack::factory()->create();

        //call the api
        $response = $this->get(route('api.ec.track.feature_collection_for_pdf', ['id' => $track->id]));

        //check if the geojson has only the track feature
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('type', $json);
        $this->assertArrayHasKey('features', $json);
        $this->assertIsArray($json['features']);
        $this->assertCount(1, $json['features']);
        $this->assertEquals($json['features'][0]['properties']['type_sisteco'], 'Track');
    }
}
