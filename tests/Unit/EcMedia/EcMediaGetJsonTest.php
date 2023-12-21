<?php

namespace Tests\Unit\EcMedia;

use App\Models\EcMedia;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcMediaGetJsonTest extends TestCase
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

    public function testJson()
    {
        $media = EcMedia::factory()->create();
        $json = $media->getJson();
        $api_url = route('api.ec.media.geojson', ['id' => $media->id], true);

        $this->assertEquals($media->id, $json['id']);
        $this->assertEquals($media->url, $json['url']);
        //        $this->assertEquals($media->description, $json['caption']);
        $this->assertEquals($api_url, $json['api_url']);

        // SIZES
        $this->assertIsArray($json['sizes']);
        $this->assertCount(4, $json['sizes']);

        $this->assertArrayHasKey('108x137', $json['sizes']);
        $this->assertArrayHasKey('108x148', $json['sizes']);
        $this->assertArrayHasKey('100x200', $json['sizes']);
        $this->assertArrayHasKey('original', $json['sizes']);
    }
}
