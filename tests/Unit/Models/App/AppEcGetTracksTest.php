<?php

namespace Tests\Unit\Models\App;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;

class AppEcGetTracksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function when_app_api_is_null_it_returns_user_tracks()
    {
        $app = $this->_create_app(null);
        $this->assertEquals(2, $app->getEcTracks()->count());
    }

    /**
     * @test
     */
    public function when_app_api_is_elbrus_it_returns_user_tracks()
    {
        $app = $this->_create_app('elbrus');
        $this->assertEquals(2, $app->getEcTracks()->count());
    }

    /**
     * @test
     */
    public function when_app_api_is_webmapp_it_returns_all_tracks()
    {
        $app = $this->_create_app('webmapp');
        $this->assertEquals(4, $app->getEcTracks()->count());
    }

    private function _create_app(?string $api): App
    {
        $this->mock(HoquServiceProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->atLeast(1);
        });
        $u1 = User::factory()->create();
        $t11 = EcTrack::factory()->create(['user_id' => $u1->id]);
        $t11->user_id = $u1->id;
        $t11->save();
        $t12 = EcTrack::factory()->create(['user_id' => $u1->id]);
        $t12->user_id = $u1->id;
        $t12->save();

        $u2 = User::factory()->create();
        $t21 = EcTrack::factory()->create(['user_id' => $u2->id]);
        $t21->user_id = $u2->id;
        $t21->save();
        $t22 = EcTrack::factory()->create(['user_id' => $u2->id]);
        $t22->user_id = $u2->id;
        $t22->save();

        $app = App::factory()->create(['user_id' => $u1->id, 'api' => $api]);
        $app->user_id = $u1->id;

        foreach (EcTrack::all() as $t) {
            Log::info("ID:{$t->id} USER:{$t->user_id}");
        }

        return $app;
    }
}
