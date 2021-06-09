<?php

namespace Tests\Feature;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Providers\HoquServiceProvider;
use Doctrine\DBAL\Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EcPoiTest extends TestCase
{
    use RefreshDatabase;

    public function testSaveEcPoiOk()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_poi', ['id' => 1])
                ->andReturn(201);
        });
        $ecPoi = new EcPoi(['name' => 'testName', 'url' => 'testUrl']);
        $ecPoi->id = 1;
        $ecPoi->save();
    }

    public function testSaveEcPoiError()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_poi', ['id' => 1])
                ->andThrows(new Exception());
        });
        Log::shouldReceive('error')
            ->once();
        $ecPoi = new EcPoi(['name' => 'testName', 'url' => 'testUrl']);
        $ecPoi->id = 1;
        $ecPoi->save();
    }

    public function testAssociateEvidenceImageToPoi()
    {
        $ecPoi = EcPoi::factory()->create();
        $this->assertIsObject($ecPoi);

        EcMedia::factory(50)->create();
        $ecMedia = EcMedia::all()->random();
        $ecPoi->evidence_image = $ecMedia->id;
        $ecPoi->save();

        $this->assertEquals($ecPoi->evidence_image, $ecMedia->id);
    }
}
