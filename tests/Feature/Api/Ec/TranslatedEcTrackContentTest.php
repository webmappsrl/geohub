<?php

namespace Tests\Feature\Api\Ec;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\TaxonomyActivity;
use App\Models\User;
use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;


/**
 * Class TranslatedEcTrackContentTest
 * Test API translated content
 * api/ec/track/{id}
 * api/ec/track/{id}.geojson
 * api/ec/track/download/{id}
 * api/ec/track/download/{id}.geojson
 * api/app/elbrus/{app_id}/geojson/ec_track_{track_id}.geojson
 * api/app/elbrus/{app_id}/geojson/ec_track_{track_id}.json
 * api/app/elbrus/{app_id}/taxonomies/track_{taxonomy_name}_{term_id}.json
 * @package Tests\Feature
 */
class TranslatedEcTrackContentTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    private $fields=['name','excerpt','description'];

    /**
     * api/ec/track/{id}
     */
    public function testApiEcTrack() {
        $data = $this->_getData();
        $track = \App\Models\EcTrack::factory()->create($data);
        $geojson = $this->getJson('api/ec/track/'.$track->id);
        $this->assertArrayHasKey('properties',$geojson);
        $p = $geojson['properties'];
        foreach($this->fields as $field) {
            $this->assertArrayHasKey($field,$p);
            $this->assertArrayHasKey('it',$p[$field]);
            $this->assertArrayHasKey('en',$p[$field]);
            $this->assertEquals($data[$field]['it'],$p[$field]['it']);
            $this->assertEquals($data[$field]['en'],$p[$field]['en']);
        }
    }

    /**
     * api/ec/track/{id}.geojson
     */
    public function testApiEcTrackGeojson() {
        $data = $this->_getData();
        $track = \App\Models\EcTrack::factory()->create($data);
        $geojson = $this->getJson('api/ec/track/'.$track->id.'.geojson');
        $this->assertArrayHasKey('properties',$geojson);
        $p = $geojson['properties'];
        foreach($this->fields as $field) {
            $this->assertArrayHasKey($field,$p);
            $this->assertArrayHasKey('it',$p[$field]);
            $this->assertArrayHasKey('en',$p[$field]);
            $this->assertEquals($data[$field]['it'],$p[$field]['it']);
            $this->assertEquals($data[$field]['en'],$p[$field]['en']);
        }
    }

    /**
     * api/ec/track/download/{id}
     */
    public function testApiEcTrackDownload() {
        $data = $this->_getData();
        $track = \App\Models\EcTrack::factory()->create($data);
        $geojson = $this->getJson('api/ec/track/download/'.$track->id);
        $this->assertArrayHasKey('properties',$geojson);
        $p = $geojson['properties'];
        foreach($this->fields as $field) {
            $this->assertArrayHasKey($field,$p);
            $this->assertArrayHasKey('it',$p[$field]);
            $this->assertArrayHasKey('en',$p[$field]);
            $this->assertEquals($data[$field]['it'],$p[$field]['it']);
            $this->assertEquals($data[$field]['en'],$p[$field]['en']);
        }
    }

    /**
     * api/ec/track/download/{id}.geojson
     */
    public function testApiEcTrackDownloadGeojson() {
        $data = $this->_getData();
        $track = \App\Models\EcTrack::factory()->create($data);
        $geojson = $this->getJson('api/ec/track/download/'.$track->id.'.geojson');
        $this->assertArrayHasKey('properties',$geojson);
        $p = $geojson['properties'];
        foreach($this->fields as $field) {
            $this->assertArrayHasKey($field,$p);
            $this->assertArrayHasKey('it',$p[$field]);
            $this->assertArrayHasKey('en',$p[$field]);
            $this->assertEquals($data[$field]['it'],$p[$field]['it']);
            $this->assertEquals($data[$field]['en'],$p[$field]['en']);
        }
    }

    /**
     * api/app/elbrus/{app_id}/geojson/ec_track_{track_id}.geojson
     */
    public function testApiAppElbrusEcTrackGeojson() {
        $data = $this->_getData();
        $app = App::factory()->create();
        $track = \App\Models\EcTrack::factory()->create($data);
        $geojson = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.geojson', []);

        $this->assertArrayHasKey('properties',$geojson);
        $p = $geojson['properties'];
        foreach($this->fields as $field) {
            $this->assertArrayHasKey($field,$p);
            $this->assertArrayHasKey('it',$p[$field]);
            $this->assertArrayHasKey('en',$p[$field]);
            $this->assertEquals($data[$field]['it'],$p[$field]['it']);
            $this->assertEquals($data[$field]['en'],$p[$field]['en']);
        }
    }

    /**
     * api/app/elbrus/{app_id}/geojson/ec_track_{track_id}.json
     */
    public function testApiAppElbrusEcTrackJson() {
        $data = $this->_getData();
        $app = App::factory()->create();
        $track = \App\Models\EcTrack::factory()->create($data);
        $p = $this->getJson('/api/app/elbrus/' . $app->id . '/geojson/ec_track_' . $track->id . '.json', []);

        foreach($this->fields as $field) {
            $this->assertArrayHasKey($field,$p);
            $this->assertArrayHasKey('it',$p[$field]);
            $this->assertArrayHasKey('en',$p[$field]);
            $this->assertEquals($data[$field]['it'],$p[$field]['it']);
            $this->assertEquals($data[$field]['en'],$p[$field]['en']);
        }
    }

    /**
     * api/app/elbrus/{app_id}/taxonomies/track_{taxonomy_name}_{term_id}.json
     */
    public function testApiAppElbrusEcTaxonomiesTrack() {

        $user = User::factory()->create();
        $image = EcMedia::factory()->create();
        $activity = TaxonomyActivity::factory()->create();

        $data = $this->_getData();

        $track1 = EcTrack::factory()->create($data);
        $track1->user_id = $user->id;
        $track1->featureImage()->associate($image);
        $track1->ecMedia()->attach($image);
        $track1->save();
        $track1->taxonomyActivities()->attach([$activity->id]);

        $track2 = EcTrack::factory()->create($data);
        $track2->user_id = $user->id;
        $track1->featureImage()->associate($image);
        $track2->ecMedia()->attach($image);
        $track2->save();
        $track2->taxonomyActivities()->attach([$activity->id]);

        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();

        $uri = "api/app/elbrus/{$app->id}/taxonomies/track_activity_{$activity->id}.json";
        $result = $this->getJson($uri);
        $this->assertEquals(200, $result->getStatusCode());

        $tracks= json_decode($result->content(), true);
        $this->assertIsArray($tracks);

        $this->assertCount(2, $tracks);

        foreach($tracks as $p) {
            foreach($this->fields as $field) {
                $this->assertArrayHasKey($field,$p);
                $this->assertArrayHasKey('it',$p[$field]);
                $this->assertArrayHasKey('en',$p[$field]);
                $this->assertEquals($data[$field]['it'],$p[$field]['it']);
                $this->assertEquals($data[$field]['en'],$p[$field]['en']);
            }
        }
    }



    private function _getData() {
        return [
            'name' => [
                'it' => $this->faker->name(),
                'en' => $this->faker->name()
            ],
            'excerpt' => [
                'it' => $this->faker->text(90),
                'en' => $this->faker->text(90),
            ],
            'description' => [
                'it' => $this->faker->paragraph(5),
                'en' => $this->faker->paragraph(5)
            ]
        ];
    }
}
