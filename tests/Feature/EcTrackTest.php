<?php

namespace Tests\Feature;

use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Doctrine\DBAL\Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EcTrackTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_ec_track_ok()
    {
        $user = User::factory()->create();
        $this->be($user);
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

    public function test_save_ec_track_error()
    {
        $user = User::factory()->create();
        $this->be($user);
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_track', ['id' => 1])
                ->andThrows(new Exception);
        });
        Log::shouldReceive('error')
            ->once();
        $ecTrack = new EcTrack(['name' => 'testName']);
        $ecTrack->id = 1;
        $ecTrack->save();
    }

    public function test_associate_feature_image_to_track()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
        $ecTrack = EcTrack::factory()->create();
        $this->assertIsObject($ecTrack);

        EcMedia::factory(2)->create();
        $ecMedia = EcMedia::all()->random();
        $ecTrack->feature_image = $ecMedia->id;
        $ecTrack->save();

        $this->assertEquals($ecTrack->feature_image, $ecMedia->id);
    }

    public function test_load2_d_geojson_file()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
        $ecTrack = EcTrack::factory()->create();
        $this->assertIsObject($ecTrack);

        $name = '2d.geojson';
        $stub = __DIR__.'/Stubs/'.$name;
        $path = sys_get_temp_dir().'/'.$name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, 'application/json', null, true);
        $content = $file->getContent();
        $this->assertJson($content);

        $geometry = $ecTrack->fileToGeometry($content);
        $ecTrack->geometry = $geometry;
        $ecTrack->save();
    }

    public function test_load3_d_geojson_file()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
        $ecTrack = EcTrack::factory()->create();
        $this->assertIsObject($ecTrack);

        $name = '3d.geojson';
        $stub = __DIR__.'/Stubs/'.$name;
        $path = sys_get_temp_dir().'/'.$name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, 'application/json', null, true);
        $content = $file->getContent();
        $this->assertJson($content);

        $geometry = $ecTrack->fileToGeometry($content);
        $ecTrack->geometry = $geometry;
        $ecTrack->save();
    }

    public function test_load_feature_collection_geojson_file()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
        $ecTrack = EcTrack::factory()->create();
        $this->assertIsObject($ecTrack);

        $name = 'featureCollection.geojson';
        $stub = __DIR__.'/Stubs/'.$name;
        $path = sys_get_temp_dir().'/'.$name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, 'application/json', null, true);
        $content = $file->getContent();
        $this->assertJson($content);

        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_track', ['id' => 1])
                ->andReturn(201);
        });

        $geometry = $ecTrack->fileToGeometry($content);
        $ecTrack->id = 1;
        $ecTrack->geometry = $geometry;
        $ecTrack->save();
    }

    public function test_load_gpx_file()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
        $ecTrack = EcTrack::factory()->create();
        $this->assertIsObject($ecTrack);

        $name = 'ec_track.gpx';
        $stub = __DIR__.'/Stubs/'.$name;
        $path = sys_get_temp_dir().'/'.$name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, 'application/json', null, true);
        $content = $file->getContent();

        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_track', ['id' => 1])
                ->andReturn(201);
        });

        $geometry = $ecTrack->fileToGeometry($content);
        $ecTrack->id = 1;
        $ecTrack->geometry = $geometry;
        $ecTrack->save();
    }

    /**
     * 0.1.8.05 Come GC voglio che le tassonomie WHERE si aggiornino automaticamente
     * quando cambio la geometria del Track perché altrimenti sarebbero potenzialmente sbagliate
     */
    public function test_ec_track_change_geometry()
    {
        $user = User::factory()->create();
        $this->be($user);
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_track', ['id' => 1])
                ->andReturn(201);
        });
        $ecTrack = new EcTrack(['name' => 'testName']);
        $ecTrack->id = 1;
        $ecTrack->save();

        // ALTRO MOCK
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_track', ['id' => 1])
                ->andReturn(201);
        });

        $new_geometry = DB::raw("(ST_GeomFromText('LINESTRING(11 44 0, 12 45 0, 13 46 0)'))");
        $ecTrack->geometry = $new_geometry;
        $ecTrack->save();
    }

    public function test_load_kml_file()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
        $ecTrack = EcTrack::factory()->create();
        $this->assertIsObject($ecTrack);

        $name = 'ec_track.kml';
        $stub = __DIR__.'/Stubs/'.$name;
        $path = sys_get_temp_dir().'/'.$name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, 'application/json', null, true);
        $content = $file->getContent();

        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_track', ['id' => 1])
                ->andReturn(201);
        });

        $geometry = $ecTrack->fileToGeometry($content);
        $ecTrack->id = 1;
        $ecTrack->geometry = $geometry;
        $ecTrack->save();
    }

    public function test_osmid_fields()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_track', ['id' => 1])
                ->andReturn(201);
        });

        $ecTrack = EcTrack::factory()->create();
        $ecTrack->id = 1;
        $ecTrack->source_id = 'relation/126402';
        $ecTrack->import_method = 'osm';
        $ecTrack->save();

        $this->assertIsObject($ecTrack);
        $this->assertNotEmpty($ecTrack->geometry);

        // $new_geometry = DB::raw("(ST_GeomFromText('LINESTRING(11 44, 12 45, 13 46)'))");
        // $this->assertEquals('126402', $properties['source_id']);
        // $this->assertEquals('osm', $properties['source']);
        // $this->assertEquals('osm', $properties['import_method']);
    }
}
