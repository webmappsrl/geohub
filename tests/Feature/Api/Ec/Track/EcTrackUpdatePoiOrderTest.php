<?php

namespace Tests\Feature;

use App\Models\EcPoi;
use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcTrackUpdatePoiOrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     *
     * @test
     */
    public function with_related_pois_order_parameter_then_poi_order_is_changed()
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

        $payload = [
            'related_pois_order' => [
                $poi2->id,
                $poi4->id,
                $poi1->id,
                $poi3->id,
            ],
        ];

        // CALL API UPLOAD
        $result = $this->putJson('/api/ec/track/update/'.$track->id, $payload);

        // ASSERT
        $this->assertEquals(200, $result->getStatusCode());

        $track_updated = EcTrack::find($track->id);
        $pois_id = $track_updated->ecPois()->get()->pluck('id')->toArray();

        $this->assertEquals($poi2->id, $pois_id[0]);
        $this->assertEquals($poi4->id, $pois_id[1]);
        $this->assertEquals($poi1->id, $pois_id[2]);
        $this->assertEquals($poi3->id, $pois_id[3]);

    }

    /**
     * A basic feature test example.
     *
     * @return void
     *
     * @test
     */
    public function with_related_pois_order_parameter_then_poi_order_is_changed_also_with_invalid_poi_id()
    {
        // WHEN
        $poi1 = EcPoi::factory()->create();
        $poi2 = EcPoi::factory()->create();
        $poi3 = EcPoi::factory()->create();
        $poi4 = EcPoi::factory()->create();

        // NOT RELATED
        $poi5 = EcPoi::factory()->create();
        $track = EcTrack::factory()->create();

        $track->ecPois()->attach($poi1);
        $track->ecPois()->attach($poi2);
        $track->ecPois()->attach($poi3);
        $track->ecPois()->attach($poi4);

        $payload = [
            'related_pois_order' => [
                $poi2->id,
                $poi4->id,
                $poi1->id,
                $poi3->id,
                $poi5->id,
            ],
        ];

        // CALL API UPLOAD
        $result = $this->putJson('/api/ec/track/update/'.$track->id, $payload);

        // ASSERT
        $this->assertEquals(200, $result->getStatusCode());

        $track_updated = EcTrack::find($track->id);
        $pois_id = $track_updated->ecPois()->get()->pluck('id')->toArray();

        $this->assertEquals($poi2->id, $pois_id[0]);
        $this->assertEquals($poi4->id, $pois_id[1]);
        $this->assertEquals($poi1->id, $pois_id[2]);
        $this->assertEquals($poi3->id, $pois_id[3]);

    }
}
