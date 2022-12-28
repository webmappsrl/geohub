<?php

namespace Tests\Unit\Providers;

use App\Providers\CurlServiceProvider;
use App\Providers\OsmServiceProvider;
use Exception;
use Mockery\MockInterface;
use Tests\TestCase;


class OsmServiceProvidergetPropertiesAndGeometryForNodeTest extends TestCase
{
    // Exceptions
    /** @test */
    public function no_elements_throw_exception() {
        $osmid = 'node/1234';
        $return = json_encode([
            'version'=>'0.6',
        ]);
        $url = 'https://api.openstreetmap.org/api/0.6/node/1234.json';
        $mock = $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($url,$return) {
            $mock->shouldReceive('exec')
                 ->once()
                 ->with($url)
                 ->andReturn($return);
        });
        $osmp = app(OsmServiceProvider::class);
        $this->expectException(Exception::class);
        $osmp->getPropertiesAndGeometry($osmid);
        $this->assertTrue(false);
    }
    /** @test */
    public function no_tags_throw_exception() {
        $osmid = 'node/1234';
        $return = json_encode([
            'version'=>'0.6',
            'elements'=>[
                'id' => 1234,
                'type' => 'node',
                'lat' => 44.3790892,
                'lon' => 10.2960082,
            ]
        ]);
        $url = 'https://api.openstreetmap.org/api/0.6/node/1234.json';
        $mock = $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($url,$return) {
            $mock->shouldReceive('exec')
                 ->once()
                 ->with($url)
                 ->andReturn($return);
        });
        $osmp = app(OsmServiceProvider::class);
        $this->expectException(Exception::class);
        $osmp->getPropertiesAndGeometry($osmid);
        $this->assertTrue(false);
    }
    /** @test */
    public function no_lat_throw_exception() {
        $osmid = 'node/1234';
        $return = json_encode([
            'version'=>'0.6',
            'elements'=>[
                [
                    'id' => 1234,
                    'type' => 'node',
                    'lon' => 10.2960082,
                    'tags' => [
                    'name' => 'Some name',
                    ]
                ]
            ]
        ]);
        $url = 'https://api.openstreetmap.org/api/0.6/node/1234.json';
        $mock = $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($url,$return) {
            $mock->shouldReceive('exec')
                 ->once()
                 ->with($url)
                 ->andReturn($return);
        });
        $osmp = app(OsmServiceProvider::class);
        $this->expectException(Exception::class);
        $osmp->getPropertiesAndGeometry($osmid);
        $this->assertTrue(false);
    }
    /** @test */
    public function no_lon_throw_exception() {
        $osmid = 'node/1234';
        $return = json_encode([
            'version'=>'0.6',
            'elements'=>[
                [
                    'id' => 1234,
                    'type' => 'node',
                    'lat' => 44.3790892,
                    'tags' => [
                    'name' => 'Some name',
                    ]
                ]
            ]
        ]);
        $url = 'https://api.openstreetmap.org/api/0.6/node/1234.json';
        $mock = $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($url,$return) {
            $mock->shouldReceive('exec')
                 ->once()
                 ->with($url)
                 ->andReturn($return);
        });
        $osmp = app(OsmServiceProvider::class);
        $this->expectException(Exception::class);
        $osmp->getPropertiesAndGeometry($osmid);
        $this->assertTrue(false);
    }

    // Positive test
    /** @test */
    public function with_proper_json_has_proper_properties_and_geometry() {
        $osmid = 'node/1234';
        $return = json_encode([
            'version'=>'0.6',
            'elements'=>[
                [
                    'id' => 1234,
                    'type' => 'node',
                    'lat' => 44.3790892,
                    'lon' => 10.2960082,
                    'timestamp' => '2017-02-11T13:35:13Z',
                    'tags' => [
                        'name' => 'Some name',
                    ],
                ]
            ]
        ]);
        $url = 'https://api.openstreetmap.org/api/0.6/node/1234.json';
        $mock = $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($url,$return) {
            $mock->shouldReceive('exec')
                 ->once()
                 ->with($url)
                 ->andReturn($return);
        });
        $osmp = app(OsmServiceProvider::class);
        $val = $osmp->getPropertiesAndGeometry($osmid);
        $this->assertIsArray($val);
        $this->assertEquals(2,count($val));
        $this->assertIsArray($val[0]);
        $this->assertIsArray($val[1]);

        $properties = $val[0];
        $this->assertArrayHasKey('name',$properties);
        $this->assertEquals('Some name',$properties['name']);
        $this->assertArrayHasKey('_updated_at',$properties);
        $this->assertEquals('2017-02-11 13:35:13',$properties['_updated_at']);

        $geometry = $val[1];
        $this->assertIsArray($geometry);
        $this->assertArrayHasKey('type',$geometry);
        $this->assertEquals('Point',$geometry['type']);
        $this->assertArrayHasKey('coordinates',$geometry);
        $coordinates=[10.2960082,44.3790892];
        $this->assertEquals($coordinates,$geometry['coordinates']);




    }
}
