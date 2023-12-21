<?php

namespace Tests\Feature;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\TaxonomyPoiType;
use App\Providers\HoquServiceProvider;
use Doctrine\DBAL\Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $ecPoi->user_id = 1;
        $ecPoi->save();
    }

    /**
     * 0.1.7.11 Come GC voglio che le tassonomie WHERE si aggiornino automaticamente
     * quando cambio la geometria del punto perché altrimenti sarebbero potenzialmente sbagliate
     */
    public function testEcPoiChangeGeometry()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_poi', ['id' => 1])
                ->andReturn(201);
        });
        $geometry = DB::raw("(ST_GeomFromText('POINT(10 43)'))");
        $ecPoi = new EcPoi(['name' => 'testName', 'url' => 'testUrl', 'geometry' => $geometry]);
        $ecPoi->id = 1;
        $ecPoi->user_id = 1;
        $ecPoi->save();

        // ALTRO MOCK
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_poi', ['id' => 1])
                ->andReturn(201);
        });

        $new_geometry = DB::raw("(ST_GeomFromText('POINT(11 44)'))");
        $ecPoi->geometry = $new_geometry;
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
        $ecPoi->user_id = 1;
        $ecPoi->save();
    }

    public function testAssociateFeatureImageToPoi()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
        $ecPoi = EcPoi::factory()->create();
        $this->assertIsObject($ecPoi);

        EcMedia::factory(2)->create();
        $ecMedia = EcMedia::all()->random();
        $ecPoi->feature_image = $ecMedia->id;
        $ecPoi->save();

        $this->assertEquals($ecPoi->feature_image, $ecMedia->id);
    }

    public function testContactFields()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
        $ecPoi = EcPoi::factory()->create();
        $this->assertIsObject($ecPoi);
        $this->assertNotEmpty($ecPoi->contact_phone);
        $this->assertNotEmpty($ecPoi->contact_email);
    }

    public function testAssociateTaxonomyPoiTypeToEcPoi()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
        $taxonomies = TaxonomyPoiType::factory(2)->create();
        $ecPoi = EcPoi::factory()->create();
        $this->assertIsObject($ecPoi);

        foreach ($taxonomies as $taxonomy) {
            $ecPoi->taxonomyPoiTypes()->attach([$taxonomy->id]);
        }

        $this->assertEquals(2, $ecPoi->taxonomyPoiTypes()->count());
    }

    public function testExistsEleField()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
        $ecPoi = EcPoi::factory()->create();
        $this->assertIsObject($ecPoi);
        $this->assertNotEmpty($ecPoi->ele);
    }
}
