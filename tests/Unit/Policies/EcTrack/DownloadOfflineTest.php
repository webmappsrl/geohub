<?php

namespace Tests\Unit\Policies\EcTrack;

use App\Models\EcTrack;
use App\Models\Partnership;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DownloadOfflineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function test_can_not_download_offline_when_no_partnerships()
    {
        $user = User::factory()->create();
        $track = EcTrack::factory()->create();

        $result = Gate::forUser($user)->allows('downloadOffline', $track);
        $this->assertFalse($result);
    }

    public function test_can_not_download_offline_when_partnership_only_on_track()
    {
        $user = User::factory()->create();
        $track = EcTrack::factory()->create();
        $partnership = Partnership::factory()->create();

        $track->partnerships()->attach($partnership->id);

        $result = Gate::forUser($user)->allows('downloadOffline', $track);
        $this->assertFalse($result);
    }

    public function test_can_not_download_offline_when_partnership_only_on_user()
    {
        $user = User::factory()->create();
        $track = EcTrack::factory()->create();
        $partnership = Partnership::factory()->create();

        $user->partnerships()->attach($partnership->id);

        $result = Gate::forUser($user)->allows('downloadOffline', $track);
        $this->assertFalse($result);
    }

    public function test_can_download_offline()
    {
        $user = User::factory()->create();
        $track = EcTrack::factory()->create();
        $partnership = Partnership::factory()->create();

        $track->partnerships()->attach($partnership->id);
        $user->partnerships()->attach($partnership->id);

        $result = Gate::forUser($user)->allows('downloadOffline', $track);
        $this->assertTrue($result);
    }
}
