<?php

namespace Tests\Feature\Api\App;

use App\Models\App;
use App\Models\EcMedia;
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
    public function testGetTracksByTaxonomyTermActivity()
    {
        $user = User::factory()->create();
        $image = EcMedia::factory()->create();
        $activity = TaxonomyActivity::factory()->create();
        $track1 = EcTrack::factory()->create();
        $track1->user_id = $user->id;
        $track1->featureImage()->associate($image);
        $track1->ecMedia()->attach($image);
        $track1->save();
        $track1->taxonomyActivities()->attach([$activity->id]);

        $track2 = EcTrack::factory()->create();
        $track2->user_id = $user->id;
        $track2->featureImage()->associate($image);
        $track2->ecMedia()->attach($image);
        $track2->save();
        $track2->taxonomyActivities()->attach([$activity->id]);

        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();

        $uri = "api/app/elbrus/{$app->id}/taxonomies/track_activity_{$activity->id}.json";
        $result = $this->getJson($uri);
        $this->assertEquals(200, $result->getStatusCode());

        $tracks = json_decode($result->content(), true);
        $this->assertIsArray($tracks);

        $this->assertCount(2, $tracks);

        $fields = [
            'id', 'description', 'excerpt', 'source_id', 'import_method', 'source', 'distance', 'ascent', 'descent',
            'ele_from', 'ele_to', 'ele_min', 'ele_max', 'duration_forward', 'duration_backward',
            'ele:from', 'ele:to', 'ele:min', 'ele:max', 'duration:forward', 'duration:backward',
            'image', 'imageGallery'
        ];

        foreach ($fields as $field) {
            $this->assertArrayHasKey($field, $tracks[0]);
        }

        /** @todo: differenziare tassonomia */
        /** @todo: controllare difficulty */
        // $this->assertEquals($track2->id, $tracks[0]['id']);
    }
}
