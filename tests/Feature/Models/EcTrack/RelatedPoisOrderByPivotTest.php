<?php

namespace Tests\Feature;

use App\Models\EcPoi;
use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RelatedPoisOrderByPivotTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     * @test
     */
    public function whenOrderIsProperlySetRelatedPoisAreProperlyOrdered() 
    {
        $poi1 = EcPoi::factory()->create();
        $poi2 = EcPoi::factory()->create();
        $track = EcTrack::factory()->create();

        $track->ecPois()->attach($poi1);
        $track->ecPois()->attach($poi2);

        $this->assertEquals(2,$track->ecPois()->count());

        $track->ecPois()->updateExistingPivot($poi1,['order'=>1]);
        $pois = $track->ecPois()->get();
        $this->assertEquals($poi2->id,$pois[0]->id);
        $this->assertEquals($poi1->id,$pois[1]->id);

        $track->ecPois()->updateExistingPivot($poi1,['order'=>0]);
        $track->ecPois()->updateExistingPivot($poi2,['order'=>1]);
        $pois = $track->ecPois()->get();
        $this->assertEquals($poi1->id,$pois[0]->id);
        $this->assertEquals($poi2->id,$pois[1]->id);

    }
    /**
     * A basic feature test example.
     *
     * @return void
     * @test
     */
    public function whenOrderIsProperlySetRelatedPoisAreProperlyOrderedAlsoWithFourPois() 
    {
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

        $pois = $track->ecPois()->get();
        $this->assertEquals($poi2->id,$pois[0]->id);
        $this->assertEquals($poi4->id,$pois[1]->id);
        $this->assertEquals($poi1->id,$pois[2]->id);
        $this->assertEquals($poi3->id,$pois[3]->id);
        
    }
}
