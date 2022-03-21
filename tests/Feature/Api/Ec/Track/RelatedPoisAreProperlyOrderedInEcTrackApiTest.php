<?php

namespace Tests\Feature;

use App\Models\EcPoi;
use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RelatedPoisAreProperlyOrderedInEcTrackApiTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     * @test
     */
    public function when_relates_pois_pivot_order_field_is_set_then_related_poi_in_ectrack_api_are_properly_ordered()
    {
        // WHEN
        $poi1 = EcPoi::factory()->create();
        $poi2 = EcPoi::factory()->create();
        $poi3 = EcPoi::factory()->create();
        $poi4 = EcPoi::factory()->create();
        $track = EcTrack::factory()->create();

        $track->ecPois()->attach($poi1);
        $track->ecPois()->attach($poi2);
        $track->ecPois()->attach($poi3);
        $track->ecPois()->attach($poi4);

        $this->assertEquals(4,$track->ecPois()->count());

        $track->ecPois()->updateExistingPivot($poi2,['order'=>0]);
        $track->ecPois()->updateExistingPivot($poi4,['order'=>1]);
        $track->ecPois()->updateExistingPivot($poi1,['order'=>2]);
        $track->ecPois()->updateExistingPivot($poi3,['order'=>3]);

        // ACTION
        $response = $this->get(route('api.ec.track.json', ['id' => $track->id]));
        $response->assertStatus(200);
        $json = $response->json();

        // ASSERT
        $this->assertIsArray($json);
        $this->assertArrayHasKey('properties', $json);
        $this->assertIsArray($json['properties']);
        $this->assertArrayHasKey('related_pois', $json['properties']);
        $this->assertIsArray($json['properties']['related_pois']);

        $pois = $json['properties']['related_pois'];
        $this->assertEquals(4,count($pois));
        $this->assertEquals($poi2->id,$pois[0]['properties']['id']);
        $this->assertEquals($poi4->id,$pois[1]['properties']['id']);
        $this->assertEquals($poi1->id,$pois[2]['properties']['id']);
        $this->assertEquals($poi3->id,$pois[3]['properties']['id']);

    }
}
