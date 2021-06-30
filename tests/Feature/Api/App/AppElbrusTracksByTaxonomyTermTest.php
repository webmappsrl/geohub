<?php

namespace Tests\Feature\Api\App;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyWhere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppElbrusTracksByTaxonomyTermTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testGetTracksByTaxonomyTerm()
    {
        $user = User::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track1 = EcTrack::factory()->create();
        $track1->user_id = $user->id;
        $track1->save();
        $track1->taxonomyActivities()->attach([$activity->id]);

        $track2 = EcTrack::factory()->create();
        $track2->user_id = $user->id;
        $track2->save();
        $track2->taxonomyActivities()->attach([$activity->id]);

        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();

        $uri = "api/app/elbrus/{$app->id}/taxonomies/track_activity_{$activity->id}.json";
        $result = $this->getJson($uri);
        $this->assertEquals(200, $result->getStatusCode());

        $json = json_decode($result->content(), true);

        $this->assertArrayHasKey('tracks', $json);
        $this->assertCount(2, $json['tracks']);
        $this->assertArrayHasKey('id', $json['tracks'][0]);
        $this->assertArrayHasKey('name', $json['tracks'][0]);
        $this->assertArrayHasKey('excerpt', $json['tracks'][0]);
        $this->assertArrayHasKey('feature_image', $json['tracks'][0]);
        $this->assertArrayHasKey('distance', $json['tracks'][0]);

        $this->assertEquals($track1->id, $json['tracks'][0]['id']);
        /** @todo: differenziare tassonomia */
        /** @todo: controllare difficulty */
    }
}
