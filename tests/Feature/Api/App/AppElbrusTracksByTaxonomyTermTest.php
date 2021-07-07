<?php

namespace Tests\Feature\Api\App;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\TaxonomyWhere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppElbrusTracksByTaxonomyTermTest extends TestCase
{
    private $first = true;

    private $geoapp;

    private $activity;
    private $target;
    private $where;
    private $when;
    private $theme;

    private  $fields = [
        'id', 'description', 'excerpt', 'source_id', 'import_method', 'source', 'distance', 'ascent', 'descent',
        'ele_from', 'ele_to', 'ele_min', 'ele_max', 'duration_forward', 'duration_backward',
        'ele:from', 'ele:to', 'ele:min', 'ele:max', 'duration:forward', 'duration:backward',
        'image', 'imageGallery'
    ];


    private function _setup() {
      if($this->first) {
          $this->first=false;

          $activity = TaxonomyActivity::factory()->create();
          $where = TaxonomyWhere::factory()->create();
          $when = TaxonomyWhen::factory()->create();
          $target = TaxonomyTarget::factory()->create();
          $theme = TaxonomyTheme::factory()->create();
          $user = User::factory()->create();
          $image = EcMedia::factory()->create();

          $track1 = EcTrack::factory()->create();
          $track1->user_id = $user->id;
          $track1->featureImage()->associate($image);
          $track1->ecMedia()->attach($image);
          $track1->taxonomyActivities()->attach([$activity->id]);
          $track1->taxonomyWheres()->attach([$where->id]);
          $track1->taxonomyWhens()->attach([$when->id]);
          $track1->taxonomyTargets()->attach([$target->id]);
          $track1->taxonomyThemes()->attach([$theme->id]);
          $track1->save();

          $track2 = EcTrack::factory()->create();
          $track2->user_id = $user->id;
          $track2->featureImage()->associate($image);
          $track2->ecMedia()->attach($image);
          $track2->taxonomyActivities()->attach([$activity->id]);
          $track2->taxonomyWheres()->attach([$where->id]);
          $track2->taxonomyWhens()->attach([$when->id]);
          $track2->taxonomyTargets()->attach([$target->id]);
          $track2->taxonomyThemes()->attach([$theme->id]);
          $track2->save();

          $app = App::factory()->create();
          $app->user_id = $user->id;
          $app->save();

          $this->geoapp = $app;
          $this->activity = $activity;
          $this->where = $where;
          $this->when = $when;
          $this->target = $target;
          $this->theme = $theme;

      }
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testGetTracksByTaxonomyTermActivity()
    {
        $this->_testGetTracksByTaxonomyTerm('activity');
    }
    public function testGetTracksByTaxonomyTermWhere()
    {
        $this->_testGetTracksByTaxonomyTerm('where');
    }
    public function testGetTracksByTaxonomyTermWhen()
    {
        $this->_testGetTracksByTaxonomyTerm('when');
    }
    // TODO: work on target (remove _ and resolve)
    public function _testGetTracksByTaxonomyTermTarget()
    {
        $this->_testGetTracksByTaxonomyTerm('target');
    }
    public function testGetTracksByTaxonomyTermTheme()
    {
        $this->_testGetTracksByTaxonomyTerm('theme');
    }
    private function _testGetTracksByTaxonomyTerm($taxonomy_name)
    {
        $this->_setup();

        $uri = "api/app/elbrus/{$this->geoapp->id}/taxonomies/track_{$taxonomy_name}_{$this->$taxonomy_name->id}.json";
        $result = $this->getJson($uri);
        $this->assertEquals(200, $result->getStatusCode());

        $tracks = json_decode($result->content(), true);
        $this->assertIsArray($tracks);

        $this->assertCount(2, $tracks);

        foreach ($this->fields as $field) {
            $this->assertArrayHasKey($field, $tracks[0]);
        }

    }
}
