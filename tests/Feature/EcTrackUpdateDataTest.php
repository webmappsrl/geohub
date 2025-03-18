<?php

namespace Tests\Feature\Api\Ec;

use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpdateTrackDataTest extends TestCase
{
    use RefreshDatabase;

    public function baseData()
    {
        $data['name'] = 'Test track';
        $data['description'] = 'Test track description.';
        $data['osmid'] = 16134525;
        $data['geometry'] = DB::raw('SRID=4326;LINESTRING(9.794614899999999 44.723281 892,9.7945791 44.7232997 892,9.794543000000001 44.7233112 892,9.794502 44.7233149 892,9.7944557 44.7233083 892,9.794397999999999 44.7232854 892,9.7943471 44.7232508 892,9.794226999999999 44.7233402 892,9.7941313 44.7233692 892,9.7940217 44.72336 892,9.793805600000001 44.723275 919,9.7933205 44.7230093 905,9.793165 44.722881 905,9.792987699999999 44.7228353 905,9.7923136 44.722816 913,9.791959200000001 44.7228571 913,9.7916428 44.7229993 913,9.791393299999999 44.7232116 922,9.7911757 44.723309 902)');
        $data['user_id'] = User::all()->random()->id;

        return $data;
    }

    public $osm_data = '{"ascent":"1255","cai_scale":"E","descent":"0","description:it":"SP359R - Percorso 923","distance":"0.33","duration:backward":"00:05","duration:forward":"00:10","from":"SP359R","network":"lwn","operator":"CAI PIACENZA","osmc:symbol":"red:red:white_stripe:923B:black","ref":"923B","route":"hiking","source":"Regione Emilia Romagna;survey:CAI","source:id":"4DC7120C-73FE-421D-AF05-B999226640F7","to":"Percorso 923","type":"route","_roundtrip":false,"_updated_at":"2023-07-27 18:57:36","duration_forward":10,"duration_backward":5}';

    public $dem_data = '{"ele_min":333,"ele_max":922,"ele_from":896,"ele_to":909,"ascent":15,"descent":2,"distance":0.3,"duration_forward_hiking":15,"duration_backward_hiking":15,"duration_forward_bike":15,"duration_backward_bike":15,"round_trip":false,"duration_forward":15,"duration_backward":15}';

    public $manual_data = '{"ele_min":892}';

    public function test_create_track_with_osm_id()
    {
        $data = $this->baseData();
        $ecTrack = EcTrack::forceCreate($data)->refresh();
        $this->assertNotNull($ecTrack->osm_data);
        $this->assertNotEmpty($ecTrack->osm_data);
        $this->assertNotNull($ecTrack->dem_data);
        $this->assertNotEmpty($ecTrack->dem_data);
        $this->checkFields($ecTrack);
    }

    public function test_update_track_with_osm_id()
    {
        $data = $this->baseData();
        $ecTrack = EcTrack::forceCreate($data)->refresh();
        $ecTrack->osm_data = $this->osm_data;
        $ecTrack->dem_data = $this->dem_data;
        $ecTrack->save();
        $this->assertNotNull($ecTrack->osm_data);
        $this->assertNotEmpty($ecTrack->osm_data);
        $this->assertNotNull($ecTrack->dem_data);
        $this->assertNotEmpty($ecTrack->dem_data);
        $this->checkFields($ecTrack);
    }

    public function test_create_track_with_osm_id_and_data()
    {
        $data = $this->baseData();
        $data['ele_min'] = 334;
        $ecTrack = EcTrack::forceCreate($data)->refresh();
        $this->assertNotNull($ecTrack->osm_data);
        $this->assertNotEmpty($ecTrack->osm_data);
        $this->assertNotNull($ecTrack->manual_data);
        $newManualData = json_decode($ecTrack->manual_data, true);
        $this->assertEquals(334, $newManualData['ele_min']);
        $this->checkFields($ecTrack);
    }

    public function test_update_track_with_osm_id_and_data()
    {
        $data = $this->baseData();
        $data['ele_min'] = 333;
        $ecTrack = EcTrack::forceCreate($data)->refresh();
        $ecTrack->ele_min = 334;
        $ecTrack->ele_max = 354;
        $ecTrack->save();
        $ecTrack->refresh();
        $this->assertNotNull($ecTrack->osm_data);
        $this->assertNotEmpty($ecTrack->osm_data);
        $this->assertNotNull($ecTrack->manual_data);
        $newManualData = json_decode($ecTrack->manual_data, true);
        $this->assertEquals(334, $newManualData['ele_min']);
        $this->checkFields($ecTrack);
    }

    public function test_create_track_without_osm_id()
    {
        $data = $this->baseData();
        unset($data['osmid']);
        $ecTrack = EcTrack::forceCreate($data)->refresh();
        $this->assertNull($ecTrack->osmid);
    }

    public function test_update_track_without_osm_id()
    {
        $data = $this->baseData();
        unset($data['osmid']);
        $ecTrack = EcTrack::forceCreate($data)->refresh();
        $ecTrack->dem_data = $this->dem_data;
        $ecTrack->save();
        $ecTrack->refresh();
        $this->assertNull($ecTrack->osm_data);
        $this->assertEmpty($ecTrack->osm_data);
        $this->assertNull($ecTrack->manual_data);
        $this->checkFields($ecTrack);
    }

    public function test_create_track_without_osm_id_and_data()
    {
        $data = $this->baseData();
        $data['ele_min'] = 333;
        unset($data['osmid']);
        $ecTrack = EcTrack::forceCreate($data)->refresh();

        $this->assertNull($ecTrack->osmid);
        $this->assertnotnull($ecTrack->manual_data);
    }

    public function test_update_track_without_osm_id_and_data()
    {
        $data = $this->baseData();
        $data['ele_min'] = 333;
        unset($data['osmid']);
        $ecTrack = EcTrack::forceCreate($data)->refresh();

        $this->assertNull($ecTrack->osmid);
        $this->assertnotnull($ecTrack->manual_data);
        $this->checkFields($ecTrack);
        $ecTrack->ele_min = 334;
        $ecTrack->ele_max = 354;
        $ecTrack->save();
        $ecTrack->refresh();
        $this->assertNull($ecTrack->osm_data);
        $this->assertEmpty($ecTrack->osm_data);
        $this->assertNotNull($ecTrack->manual_data);
        $newManualData = json_decode($ecTrack->manual_data, true);
        $this->assertEquals(334, $newManualData['ele_min']);
        $this->assertEquals(354, $newManualData['ele_max']);
        $this->checkFields($ecTrack);
    }

    public function test_create_track_with_invalid_data()
    {
        $data = $this->baseData();
        $data['osmid'] = 'invalid';

        $this->expectException(\Illuminate\Database\QueryException::class);
        $ecTrack = EcTrack::forceCreate($data)->refresh();
    }

    public function checkFields($ecTrack)
    {
        $osmData = json_decode($ecTrack->osm_data, true);
        $demData = json_decode($ecTrack->dem_data, true);
        $manualData = json_decode($ecTrack->manual_data, true);

        foreach ($ecTrack->getDemDataFields() as $field) {
            if (! is_null($ecTrack->$field)) {
                if (isset($manualData->field) && ! is_null($manualData->$field)) {
                    $this->assertEquals($ecTrack->$field, $manualData[$field]);
                } elseif (isset($osmData->$field) && ! is_null($osmData->$field)) {
                    $this->assertEquals($ecTrack->$field, $osmData[$field]);
                } elseif (isset($demData->$field) && ! is_null($demData->$field)) {
                    $this->assertEquals($ecTrack->$field, $demData[$field]);
                }
            }
        }
    }
}
