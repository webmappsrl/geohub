<?php

namespace Tests\Feature;

use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\TaxonomyPoiType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiTrackHasAllRelatedPoiMeta extends TestCase
{
    use RefreshDatabase;
    /**
     *
     * @return void
     * @test
     */
    public function when_track_has_no_pois_api_has_no_section_related_pois() {
        $track = EcTrack::factory()->create();
        $response = $this->get(route('api.ec.track.json', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('properties', $json);
        $this->assertIsArray($json['properties']);
        $this->assertArrayNotHasKey('related_pois', $json['properties']);
    }

    /**
     *
     * @return void
     * @test
     */
    public function when_track_has_pois_api_has_section_related_pois() {
        $poi = EcPoi::factory()->create();
        $track = EcTrack::factory()->create();
        $track->ecPois()->attach($poi->id);

        $response = $this->get(route('api.ec.track.json', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('properties', $json);
        $this->assertIsArray($json['properties']);
        $this->assertArrayHasKey('related_pois', $json['properties']);
    }

    /**
     *
     * @return void
     * @test
     */
    public function when_track_has_pois_api_related_poi_section_has_all_meta() {
        $poi_type = TaxonomyPoiType::factory()->create();
        $poi = EcPoi::factory()->create();
        $poi->taxonomyPoiTypes()->attach($poi_type->id);
        $track = EcTrack::factory()->create();
        $track->ecPois()->attach($poi->id);

        $response = $this->get(route('api.ec.track.json', ['id' => $track->id]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('properties', $json);
        $this->assertIsArray($json['properties']);
        $this->assertArrayHasKey('related_pois', $json['properties']);

        // Get first POI
        $poi = $json['properties']['related_pois'][0];
        $this->assertArrayHasKey('properties', $poi);

        // Check metadata
        $to_check = [
            'id',
            'name',
            'description',
            'excerpt',
            'feature_image',
            'contact_phone',
            'contact_email',
            'related_url',
            'addr_street',
            'addr_housenumber',
            'addr_postcode',
            'opening_hours',
            'taxonomy'
        ];

        foreach($to_check as $key) {
            $this->assertArrayHasKey($key,$poi['properties']);
        }
    }



}
