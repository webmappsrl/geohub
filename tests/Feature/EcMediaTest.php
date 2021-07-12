<?php

namespace Tests\Feature;

use App\Models\EcMedia;
use App\Providers\HoquServiceProvider;
use Doctrine\DBAL\Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EcMediaTest extends TestCase
{
    use RefreshDatabase;

    public function testSaveEcMediaOk()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_media', ['id' => 1])
                ->andReturn(201);
        });
        $ecMedia = new EcMedia(['name' => 'testName', 'url' => 'testUrl']);
        $ecMedia->id = 1;
        $ecMedia->save();
    }

    public function testSaveEcMediaError()
    {
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->with('enrich_ec_media', ['id' => 1])
                ->andThrows(new Exception());
        });
        Log::shouldReceive('error')
            ->once();
        $ecMedia = new EcMedia(['name' => 'testName', 'url' => 'testUrl']);
        $ecMedia->id = 1;
        $ecMedia->save();
    }

    public function testDeleteEcMediaOk()
    {
        $ecMedia = EcMedia::factory()->create();
        $this->mock(HoquServiceProvider::class, function ($mock) use ($ecMedia) {
            $mock->shouldReceive('store')
                ->once()
                ->with('delete_ec_media_images', ['url' => $ecMedia->url, 'thumbnails' => $ecMedia->thumbnails])
                ->andReturn(201);
        });

        $ecMedia->id = 1;
        $ecMedia->save();
        $ecMedia->delete();
    }

    public function testEcMediaFieldsTranslation()
    {
        $ecMedia = EcMedia::factory()->create([
            'name' => 'Titolo media',
            'description' => 'Descrizione media',
        ]);

        $this->assertEquals('Titolo media', $ecMedia->name);
        $this->assertEquals('Titolo media', $ecMedia->getTranslation('name', 'it'));
        $this->assertEquals('Descrizione media', $ecMedia->description);
        $this->assertEquals('Descrizione media', $ecMedia->getTranslation('description', 'it'));

        $ecMedia = EcMedia::factory()->create([
            'name' => [
                'it' => 'Titolo media',
                'en' => 'Media title',
            ],
            'description' => [
                'it' => 'Descrizione media',
                'en' => 'Media description',
            ],
        ]);

        $this->assertEquals('Titolo media', $ecMedia->name);
        $this->assertEquals('Titolo media', $ecMedia->getTranslation('name', 'it'));
        $this->assertEquals('Media title', $ecMedia->getTranslation('name', 'en'));
        $this->assertEquals('Descrizione media', $ecMedia->description);
        $this->assertEquals('Descrizione media', $ecMedia->getTranslation('description', 'it'));
        $this->assertEquals('Media description', $ecMedia->getTranslation('description', 'en'));

    }
}
