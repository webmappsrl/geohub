<?php

namespace Tests\Feature\Api\Ec;

use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EcTracksUpdateEleInfoFieldsTest extends TestCase
{
    use RefreshDatabase;

    private $fields = [
        'distance' => 100,
        'ascent' => 100,
        'descent' => 100,
        'ele_min' => 100,
        'ele_max' => 100,
        'ele_from' => 100,
        'ele_to' => 100,
        'duration_forward' => '1:00',
        'duration_backward' => '1:00'
    ];

    private $updated_fields = [
        'distance' => 200,
        'ascent' => 200,
        'descent' => 200,
        'ele_min' => 200,
        'ele_max' => 200,
        'ele_from' => 200,
        'ele_to' => 200,
        'duration_forward' => '2:00',
        'duration_backward' => '2:00'
    ];

    public function testDistance() {
        $this->_testByField('distance');
    }
    public function testAscent() {
        $this->_testByField('ascent');
    }
    public function testDescent() {
        $this->_testByField('descent');
    }
    public function testEleMin() {
        $this->_testByField('ele_min');
    }
    public function testEleMax() {
        $this->_testByField('ele_max');
    }
    public function testEleFrom() {
        $this->_testByField('ele_from');
    }
    public function testEleTo() {
        $this->_testByField('ele_to');
    }
    public function testDurationForward() {
        $this->_testByField('duration_forward');
    }
    public function testDurationBackward() {
        $this->_testByField('duration_backward');
    }

    private function  _testByField($field) {
        $ecTrack = EcTrack::factory()->create($this->fields);
        $payload = [$field=>$this->updated_fields[$field]];
        $result = $this->putJson('/api/ec/track/update/' . $ecTrack->id, $payload);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsString($result->getContent());
        $ecTrackUpdated = EcTrack::find($ecTrack->id);
        $this->assertEquals($this->updated_fields[$field], $ecTrackUpdated->$field);
    }
}
