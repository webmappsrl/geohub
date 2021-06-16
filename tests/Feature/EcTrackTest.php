<?php

namespace Tests\Feature;

use App\Models\EcMedia;
use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Doctrine\DBAL\Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Symm\Gisconverter\Decoders\WKT;
use Symm\Gisconverter\Geometry\Geometry;
use Symm\Gisconverter\Geometry\LineString;
use Symm\Gisconverter\Geometry\Point;

class EcTrackTest extends TestCase
{
    use RefreshDatabase;

    public function testSaveEcTrackOk()
    {
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

    public function testSaveEcTrackError()
    {
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

    public function testAssociateFeatureImageToTrack()
    {
        $ecTrack = EcTrack::factory()->create();
        $this->assertIsObject($ecTrack);

        EcMedia::factory(2)->create();
        $ecMedia = EcMedia::all()->random();
        $ecTrack->feature_image = $ecMedia->id;
        $ecTrack->save();

        $this->assertEquals($ecTrack->feature_image, $ecMedia->id);
    }

    public function testLoad2DGeojsonFile()
    {
        $ecTrack = EcTrack::factory()->create();
        $this->assertIsObject($ecTrack);

        $name = '2d.geojson';
        $stub = __DIR__ . '/Stubs/' . $name;
        $path = sys_get_temp_dir() . '/' . $name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, 'application/json', null, true);
        $content = $file->getContent();
        $this->assertJson($content);
      
        $geometry = $ecTrack->fileToGeometry($content);
        $ecTrack->geometry = $geometry;
        $ecTrack->save();
    }

    public function testLoad3DGeojsonFile()
    {
        $ecTrack = EcTrack::factory()->create();
        $this->assertIsObject($ecTrack);

        $name = '3d.geojson';
        $stub = __DIR__ . '/Stubs/' . $name;
        $path = sys_get_temp_dir() . '/' . $name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, 'application/json', null, true);
        $content = $file->getContent();
        $this->assertJson($content);
      
        $geometry = $ecTrack->fileToGeometry($content);
        $ecTrack->geometry = $geometry;
        $ecTrack->save();
    }

    public function testLoadFeatureCollectionGeojsonFile()
    {
        $ecTrack = EcTrack::factory()->create();
        $this->assertIsObject($ecTrack);

        $name = 'featureCollection.geojson';
        $stub = __DIR__ . '/Stubs/' . $name;
        $path = sys_get_temp_dir() . '/' . $name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, 'application/json', null, true);
        $content = $file->getContent();
        $this->assertJson($content);
      
        $geometry = $ecTrack->fileToGeometry($content);
        $ecTrack->geometry = $geometry;
        $ecTrack->save();
    }

    // public function testLoadGpxFile()
    // {
    //     $ecTrack = EcTrack::factory()->create();
    //     $this->assertIsObject($ecTrack);

    //     $name = 'file.gpx';
    //     $stub = __DIR__ . '/Stubs/' . $name;
    //     $path = sys_get_temp_dir() . '/' . $name;

    //     copy($stub, $path);

    //     $file = new UploadedFile($path, $name, 'application/json', null, true);
    //     $content = $file->getContent();

    //     $decoder = new WKT();
      
    //     $geometry = $ecTrack->fileToGeometry($content);
    //     $ecTrack->geometry = $geometry;
    //     $ecTrack->save();
    // }

    // public function testLoadKmlFile()
    // {
    //     $ecTrack = EcTrack::factory()->create();
    //     $this->assertIsObject($ecTrack);

    //     $stub = __DIR__ . '/Stubs/kml.geojson';
    //     $name = 'kml.geojson';
    //     $path = sys_get_temp_dir() . '/' . $name;

    //     copy($stub, $path);

    //     $file = new UploadedFile($path, $name, 'application/json', null, true);
    //     $content = $file->getContent();
    //     $this->assertJson($content);
      
    //     $geometry = $ecTrack->fileToGeometry($content);
    //     $ecTrack->geometry = $geometry;
    //     $ecTrack->save();
    // }
}
