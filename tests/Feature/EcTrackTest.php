<?php

namespace Tests\Feature;

use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Doctrine\DBAL\Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EcTrackTest extends TestCase {
    use RefreshDatabase;

    public function testSaveEcPoiOk() {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_track', ['id' => 1])
                ->andReturn(201);
        });
        $ecTrack = new EcTrack(['name' => 'testName']);
        $ecTrack->id = 1;
        $ecTrack->save();
    }

    public function testSaveEcPoiError() {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_track', ['id' => 1])
                ->andThrows(new Exception());
        });
        Log::shouldReceive('error')
            ->once();
        $ecTrack = new EcTrack(['name' => 'testName']);
        $ecTrack->id = 1;
        $ecTrack->save();
    }
}
