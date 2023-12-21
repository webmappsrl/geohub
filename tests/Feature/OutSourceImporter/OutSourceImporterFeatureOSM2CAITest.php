<?php

namespace Tests\Feature;

use App\Classes\OutSourceImporter\OutSourceImporterFeatureOSM2CAI;
use App\Models\OutSourceFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutSourceImporterFeatureOSM2CAITest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function when_endpoint_is_osm2cai_and_type_is_track_it_reads_proper_out_source_feature()
    {
        // WHEN
        $type = 'track';
        $endpoint = 'osm2cai;pec-osmidlist-test.txt';
        $source_id = 1;

        // FIRE
        $track = new OutSourceImporterFeatureOSM2CAI($type, $endpoint, $source_id);
        $track_id = $track->importFeature();

        // VERIFY
        $out_source = OutSourceFeature::find($track_id);
        $this->assertEquals('track', $out_source->type);
        $this->assertEquals(1, $out_source->source_id);
        $this->assertEquals('osm2cai;pec-osmidlist-test.txt', $out_source->endpoint);
        $this->assertEquals(\App\Classes\OutSourceImporter\OutSourceImporterFeatureOSM2CAI::class, $out_source->provider);

    }
}
