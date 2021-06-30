<?php

namespace Tests\Unit;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\TaxonomyActivity;
use App\Models\User;
use Tests\TestCase;

class AppTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testGetTrackListByTerm()
    {
        $user = User::factory()->create();
        $app = App::factory()->create();
        $app->user_id = $user->id;
        $app->save();

        $activity = TaxonomyActivity::factory()->create();
        $track = EcTrack::factory()->create();
        $track->user_id = $user->id;
        $track->save();
        $track->taxonomyActivities()->attach([$activity->id]);

        $tracks = $app->listTracksByTerm($activity);

        $this->assertIsArray($tracks);
        /** @todo: aggiungere controlli sulla collection (tipo app e term) */
    }
}
