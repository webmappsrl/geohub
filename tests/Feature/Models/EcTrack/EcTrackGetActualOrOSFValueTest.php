<?php

namespace Tests\Feature\Models\EcTrack;

use App\Models\EcTrack;
use App\Models\OutSourceTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EcTrackGetActualOrOSFValueTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function when_from_is_not_empty_it_returns_it() {
        $osf = OutSourceTrack::factory()->create();
        $t = EcTrack::factory()->create(['out_source_feature_id'=>$osf->id]);
        $this->assertEquals($t->from,$t->getActualOrOSFValue('from'));
    }
    use RefreshDatabase;

    /**
     * @test
     */
    public function when_from_is_empty_it_returns_from_OSF() {
        $osf = OutSourceTrack::factory()->create();
        $t = EcTrack::factory()->create(['out_source_feature_id'=>$osf->id,'from'=>null]);
        $this->assertEquals($osf->tags['from'],$t->getActualOrOSFValue('from'));
    }

    /**
     * @test
     */
    public function when_from_is_empty_and_from_osf_is_empty_it_returns_null() {
        $osf = OutSourceTrack::factory()->create(['tags'=>[]]);
        $t = EcTrack::factory()->create(['out_source_feature_id'=>$osf->id,'from'=>null]);
        $this->assertEquals(null,$t->getActualOrOSFValue('from'));
    }

    /**
     * @test
     */
    public function when_to_is_not_empty_it_returns_it() {
        $osf = OutSourceTrack::factory()->create();
        $t = EcTrack::factory()->create(['out_source_feature_id'=>$osf->id]);
        $this->assertEquals($t->to,$t->getActualOrOSFValue('to'));
    }
    use RefreshDatabase;

    /**
     * @test
     */
    public function when_to_is_empty_it_returns_from_OSF() {
        $osf = OutSourceTrack::factory()->create();
        $t = EcTrack::factory()->create(['out_source_feature_id'=>$osf->id,'to'=>null]);
        $this->assertEquals($osf->tags['to'],$t->getActualOrOSFValue('to'));
    }

    /**
     * @test
     */
    public function when_to_is_empty_and_to_osf_is_empty_it_returns_null() {
        $osf = OutSourceTrack::factory()->create(['tags'=>[]]);
        $t = EcTrack::factory()->create(['out_source_feature_id'=>$osf->id,'to'=>null]);
        $this->assertEquals(null,$t->getActualOrOSFValue('to'));
    }


}
