<?php

namespace Tests\Unit\EcMedia;
use App\Models\EcMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcMediaGetJsonTest extends TestCase
{
    use RefreshDatabase;

    public function testJson() {
        $media = EcMedia::factory()->create();
        $json_string = $media->getJson();
        $api_url = route('api.ec.media.geojson',['id'=>$media->id],true);

        $this->assertIsString($json_string);
        $json = json_decode($json_string,true);


        $this->assertEquals($media->id,$json['id']);
        $this->assertEquals($media->url,$json['url']);
        $this->assertEquals($media->description,$json['caption']);
        $this->assertEquals($api_url,$json['api_url']);

        // SIZES
        $this->assertIsArray($json['sizes']);
        $this->assertCount(4,$json['sizes']);

        $this->assertArrayHasKey('108x137',$json['sizes']);
        $this->assertArrayHasKey('108x148',$json['sizes']);
        $this->assertArrayHasKey('100x200',$json['sizes']);
        $this->assertArrayHasKey('original',$json['sizes']);

    }

}
