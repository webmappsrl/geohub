<?php

namespace Tests\Feature;

use App\Models\EcTrack;
use App\Models\OutSourceTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TrackWithNoDataButWithOutSourceFeatureHasFullApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $numbers = [
        'distance',
        'ascent',
        'descent',
        'ele_min',
        'ele_max',
        'ele_from',
        'ele_to',
        'duration_forward',
        'duration_backward',
    ];

    /**
     * @test
     */
    public function ec_track_api_has_out_source_name() {
        $this->ec_track_api_has_out_source_variable('name');
    }

    /**
     * @test
     */
    public function ec_track_api_has_out_source_description() {
        $this->ec_track_api_has_out_source_variable('description');
    }

    /**
     * @test
     */
    public function ec_track_api_has_out_source_excerpt() {
        $this->ec_track_api_has_out_source_variable('excerpt');
    }

    /**
     * @test
     */
    public function ec_track_api_has_out_source_distance() {
        $this->ec_track_api_has_out_source_variable('distance');
    }
    /**
     * @test
     */
    public function ec_track_api_has_out_source_ascent() {
        $this->ec_track_api_has_out_source_variable('ascent');
    }
    /**
     * @test
     */
    public function ec_track_api_has_out_source_descent() {
        $this->ec_track_api_has_out_source_variable('descent');
    }
    /**
     * @test
     */
    public function ec_track_api_has_out_source_ele_from() {
        $this->ec_track_api_has_out_source_variable('ele_from');
    }
    /**
     * @test
     */
    public function ec_track_api_has_out_source_ele_to() {
        $this->ec_track_api_has_out_source_variable('ele_to');
    }
    /**
     * @test
     */
    public function ec_track_api_has_out_source_ele_max() {
        $this->ec_track_api_has_out_source_variable('ele_max');
    }
    /**
     * @test
     */
    public function ec_track_api_has_out_source_ele_min() {
        $this->ec_track_api_has_out_source_variable('ele_min');
    }
    /**
     * @test
     */
    public function ec_track_api_has_out_source_duration_forward() {
        $this->ec_track_api_has_out_source_variable('duration_forward');
    }
    /**
     * @test
     */
    public function ec_track_api_has_out_source_duration_backward() {
        $this->ec_track_api_has_out_source_variable('duration_backward');
    }


    private function ec_track_api_has_out_source_variable($var_name) {
        $os = $this->getOutSourceTrack();
        $track = $this->getEcTrack($os->id);
        $json = json_decode($this->getJson(route('api.ec.track.json',['id'=>$track->id]))->content());

        $this->assertTrue(isset($json->properties));
        $this->assertTrue(isset($json->properties->$var_name));
        $this->assertEquals($os->tags[$var_name],$json->properties->$var_name);

    }

    private function getOutSourceTrack(): OutSourceTrack {
        $tags = [
            'name' =>$this->faker->name(),
            'description' => $this->faker->sentence(20),
            'excerpt' => $this->faker->sentence(5),
            'from' =>$this->faker->name(),
            'to' =>$this->faker->name(),
        ];
        foreach($this->numbers as $key) {
            $tags[$key] = $this->faker->numberBetween(10,100);
        }
        $data = [
            'provider' => $this->faker->slug(),
            'source_id' => $this->faker->uuid(),
            'tags' => $tags,
        ];
        return OutSourceTrack::factory()->create($data);
    }

    private function getEcTrack($os_id): EcTrack {
        $data = [
            'excerpt' => null,
            'out_source_feature_id' => $os_id,
        ];
        $track = EcTrack::factory()->create($data);
        $track->setTranslation('name','en',null);
        $track->setTranslation('name','it',null);
        $track->setTranslation('description','en',null);
        $track->setTranslation('description','it',null);
        $track->setTranslation('excerpt','en',null);
        $track->setTranslation('excerpt','it',null);
        foreach($this->numbers as $key) {
            $track->$key=null;
        }
        $track->save();
        return $track;
    }
}


