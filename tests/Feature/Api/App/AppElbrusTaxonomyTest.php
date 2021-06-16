<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\TaxonomyWhere;
use App\Models\TaxonomyTarget;
use App\Models\User;
use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Test for texonomies API LIKE
 * https://k.webmapp.it/caipontedera/taxonomies/activity.json
 * https://k.webmapp.it/caipontedera/taxonomies/where.json
 * https://k.webmapp.it/caipontedera/taxonomies/when.json
 * https://k.webmapp.it/caipontedera/taxonomies/who.json
 * https://k.webmapp.it/caipontedera/taxonomies/theme.json
 * https://k.webmapp.it/caipontedera/taxonomies/webmapp_category.json
 *
 * implemented as /app/elbrus/{app_id}/taxonomies/{taxonomy_name}.json
 *
 * Class AppElbrusTaxonomyTest
 * @package Tests\Feature
 */
class AppElbrusTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    private $names = [
        'activity','where','when','who','theme','webmapp_category'
    ];

    public function testWrongTaxonomyReturns400() {
        $app=App::factory()->create();
        $uri = 'api/app/elbrus/'.$app->id.'/taxonomies/x.json';
        $result = $this->getJson($uri);
        $this->assertEquals(400,$result->getStatusCode());

    }

    public function testNoAppReturns404ForAllValidTaxonomyName() {
        foreach($this->names as $name) {
            $uri = "api/app/elbrus/0/taxonomies/$name.json";
            $result = $this->getJson($uri);
            $this->assertEquals(404,$result->getStatusCode());
        }
    }

    public function testAppWithNoTaxonomyReturns200EmptyForAllTaxonomy() {
        $app=App::factory()->create();
        foreach($this->names as $name) {
            $uri = "api/app/elbrus/$app->id/taxonomies/$name.json";
            $result = $this->getJson($uri);
            $this->assertEquals(200,$result->getStatusCode());
            $this->assertCount(0,json_decode($result->content(),true));
        }
    }

    public function testAppWithOneTrackWithOnlyOneActivityTerm() {
        // CONTEXT: create user, activity,track,app and relations
        $user = User::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track = EcTrack::factory()->create(); $track->user_id=$user->id;$track->save();
        $track->taxonomyActivities()->attach($activity->id);
        $app = App::factory()->create(); $app->user_id=$user->id;$app->save();

        // Check activity term
        // https://k.webmapp.it/caipontedera/taxonomies/activity.json

        $uri = "api/app/elbrus/$app->id/taxonomies/activity.json";
        $result = $this->getJson($uri);
        $this->assertEquals(200,$result->getStatusCode());

        $json = json_decode($result->content(),true);

        $this->assertArrayHasKey('activity_'.$activity->id,$json);
        $this->assertCount(1,$json);

        $json_term = $json['activity_'.$activity->id];
        $this->assertEquals('activity_'.$activity->id,$json_term['id']);
        $this->assertEquals($activity->name,$json_term['name']);
        $this->assertEquals($activity->description,$json_term['description']);
        $this->assertEquals('ec_track_'.$track->id,$json_term['items']['track'][0]);

        // Check other taxonomies

    }

    public function testAppWithOneTrackWithOnlyOneThemeTerm() {
        // CONTEXT: create user, activity,track,app and relations
        $user = User::factory()->create();
        $theme = TaxonomyTheme::factory()->create();
        $track = EcTrack::factory()->create(); $track->user_id=$user->id;$track->save();
        $track->taxonomyThemes()->attach($theme->id);
        $app = App::factory()->create(); $app->user_id=$user->id;$app->save();

        $uri = "api/app/elbrus/$app->id/taxonomies/theme.json";
        $result = $this->getJson($uri);
        $this->assertEquals(200,$result->getStatusCode());

        $json = json_decode($result->content(),true);

        $this->assertArrayHasKey('theme_'.$theme->id,$json);
        $this->assertCount(1,$json);

        $json_term = $json['theme_'.$theme->id];
        $this->assertEquals('theme_'.$theme->id,$json_term['id']);
        $this->assertEquals($theme->name,$json_term['name']);
        $this->assertEquals($theme->description,$json_term['description']);
        $this->assertEquals('ec_track_'.$track->id,$json_term['items']['track'][0]);

        // Check other taxonomies

    }

    public function testAppWithOneTrackWithOnlyOneTerm() {

        $names = ['activity','where','when','who','theme'];
        $names1 = $names;
        foreach($names as $name) {

            $user = User::factory()->create();
            $track = EcTrack::factory()->create(); $track->user_id=$user->id;$track->save();
            switch($name) {
                case 'activity':
                    $tax = TaxonomyActivity::factory()->create();
                    $track->taxonomyActivities()->attach($tax->id);
                    break;
                case 'where':
                    $tax = TaxonomyWhere::factory()->create();
                    $track->taxonomyWheres()->attach($tax->id);
                    break;
                case 'when':
                    $tax = TaxonomyWhen::factory()->create();
                    $track->taxonomyWhens()->attach($tax->id);
                    break;
                case 'who':
                    $tax = TaxonomyTarget::factory()->create();
                    $track->taxonomyTargets()->attach($tax->id);
                    break;
                case 'theme':
                    $tax = TaxonomyTheme::factory()->create();
                    $track->taxonomyThemes()->attach($tax->id);
                    break;
            }

            $app = App::factory()->create(); $app->user_id=$user->id;$app->save();

            $uri = "api/app/elbrus/$app->id/taxonomies/$name.json";
            $result = $this->getJson($uri);
            $this->assertEquals(200,$result->getStatusCode());

            $json = json_decode($result->content(),true);

            $this->assertArrayHasKey($name.'_'.$tax->id,$json);
            $this->assertCount(1,$json);

            $json_term = $json[$name.'_'.$tax->id];
            $this->assertEquals($name.'_'.$tax->id,$json_term['id']);
            $this->assertEquals($tax->name,$json_term['name']);
            $this->assertEquals($tax->description,$json_term['description']);
            $this->assertEquals('ec_track_'.$track->id,$json_term['items']['track'][0]);

            // Check other taxonomies
            foreach($names1 as $name1) {
                if($name1 != $name) {
                    $uri = "api/app/elbrus/$app->id/taxonomies/$name1.json";
                    $result = $this->getJson($uri);
                    $this->assertEquals(200,$result->getStatusCode());
                    $this->assertCount(0,json_decode($result->content(),true));

                }
            }

        }
    }

    public function testAppWithOneTrackAndTwoActivityTerms() {
        // CONTEXT: create user, activity,track,app and relations
        $user = User::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $activity1 = TaxonomyActivity::factory()->create();
        $track = EcTrack::factory()->create(); $track->user_id=$user->id;$track->save();
        $track->taxonomyActivities()->attach([$activity->id,$activity1->id]);
        $app = App::factory()->create(); $app->user_id=$user->id;$app->save();

        // Check activity term
        // https://k.webmapp.it/caipontedera/taxonomies/activity.json

        $uri = "api/app/elbrus/$app->id/taxonomies/activity.json";
        $result = $this->getJson($uri);
        $this->assertEquals(200,$result->getStatusCode());

        $json = json_decode($result->content(),true);

        $this->assertArrayHasKey('activity_'.$activity->id,$json);
        $this->assertArrayHasKey('activity_'.$activity1->id,$json);
        $this->assertCount(2,$json);

        $json_term = $json['activity_'.$activity->id];
        $this->assertEquals('activity_'.$activity->id,$json_term['id']);
        $this->assertEquals($activity->name,$json_term['name']);
        $this->assertEquals($activity->description,$json_term['description']);
        $this->assertEquals('ec_track_'.$track->id,$json_term['items']['track'][0]);

        $json_term = $json['activity_'.$activity1->id];
        $this->assertEquals('activity_'.$activity1->id,$json_term['id']);
        $this->assertEquals($activity1->name,$json_term['name']);
        $this->assertEquals($activity1->description,$json_term['description']);
        $this->assertEquals('ec_track_'.$track->id,$json_term['items']['track'][0]);


    }

    public function testAppWithTwoTracksAndOneActivityTerm() {
        // CONTEXT: create user, activity,track,app and relations
        $user = User::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track = EcTrack::factory()->create(); $track->user_id=$user->id;$track->save();
        $track->taxonomyActivities()->attach([$activity->id]);
        $track1 = EcTrack::factory()->create(); $track1->user_id=$user->id;$track1->save();
        $track1->taxonomyActivities()->attach([$activity->id]);
        $app = App::factory()->create(); $app->user_id=$user->id;$app->save();

        // Check activity term
        // https://k.webmapp.it/caipontedera/taxonomies/activity.json

        $uri = "api/app/elbrus/$app->id/taxonomies/activity.json";
        $result = $this->getJson($uri);
        $this->assertEquals(200,$result->getStatusCode());

        $json = json_decode($result->content(),true);

        $this->assertArrayHasKey('activity_'.$activity->id,$json);
        $this->assertCount(1,$json);

        $json_term = $json['activity_'.$activity->id];
        $this->assertEquals('activity_'.$activity->id,$json_term['id']);
        $this->assertEquals($activity->name,$json_term['name']);
        $this->assertEquals($activity->description,$json_term['description']);
        $this->assertTrue(in_array('ec_track_'.$track->id,$json_term['items']['track']));
        $this->assertTrue(in_array('ec_track_'.$track1->id,$json_term['items']['track']));
        $this->assertCount(2,$json_term['items']['track']);
    }

    public function testWhereTaxonomyHasNoGeometry() {
        // CONTEXT: create user, activity,track,app and relations
        $user = User::factory()->create();
        $where = TaxonomyWhere::factory()->create();
        $track = EcTrack::factory()->create(); $track->user_id=$user->id;$track->save();
        $track->taxonomyWheres()->attach([$where->id]);
        $app = App::factory()->create(); $app->user_id=$user->id;$app->save();

        $uri = "api/app/elbrus/$app->id/taxonomies/where.json";
        $result = $this->getJson($uri);
        $this->assertEquals(200,$result->getStatusCode());

        $json = json_decode($result->content(),true);

        $this->assertArrayHasKey('where_'.$where->id,$json);
        $this->assertCount(1,$json);

        $json_term = $json['where_'.$where->id];

        $this->assertFalse(isset($json_term['geometry']));

    }

}
