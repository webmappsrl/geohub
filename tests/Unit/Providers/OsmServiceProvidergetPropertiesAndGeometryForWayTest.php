<?php

namespace Tests\Unit\Providers;

use App\Providers\CurlServiceProvider;
use App\Providers\OsmServiceProvider;
use App\Providers\OsmServiceProviderExceptionNodeHasNoLat;
use App\Providers\OsmServiceProviderExceptionNodeHasNoLon;
use App\Providers\OsmServiceProviderExceptionNoTags;
use App\Providers\OsmServiceProviderExceptionWayHasNoNodes;
use Exception;
use Mockery\MockInterface;
use Tests\TestCase;

class OsmServiceProvidergetPropertiesAndGeometryForWayTest extends TestCase
{

    private function getJsonWay():string {
        return json_encode([
            'version'=>'0.6',
            'elements'=> [
                [
                    'id' => 2,
                    'type' => 'node',
                    'lat' => 44,
                    'lon' => 10,
                    'timestamp' => '2021-09-13T14:57:20Z',
                    'tags' => [
                        'name' => 'Name of node with id 2'
                    ]
                ],
                [
                    'id' => 3,
                    'type' => 'node',
                    'lat' => 45,
                    'lon' => 11,
                    'timestamp' => '2020-09-13T14:57:20Z',
                    'tags' => [
                        'name' => 'Name of node with id 3'
                    ]
                ],
                [
                    'id' => 1,
                    'type' => 'way',
                    'nodes' => [
                        2,
                        3
                    ],
                    'timestamp' => '2018-09-13T14:57:20Z',
                    'tags' => [
                        'name' => 'Name of way with id 1'
                    ]
                ],
            ] 
        ]);
    }
    // Exceptions
    /** @test */
    public function no_elements_throw_exception() {
        $osmid = 'way/1';
        $return = json_encode([
            'version'=>'0.6',
        ]);
        $url = 'https://api.openstreetmap.org/api/0.6/way/1/full.json';
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
        $osmid = 'way/1';
        $return = json_encode([
            'version'=>'0.6',
            'elements'=> [
                [
                    'id' => 2,
                    'type' => 'node',
                    'lat' => 44,
                    'lon' => 10,
                ],
                [
                    'id' => 3,
                    'type' => 'node',
                    'lat' => 45,
                    'lon' => 11,
                ],
                [
                    'id' => 1,
                    'type' => 'way',
                    'nodes' => [
                        2,
                        3
                    ]
                ],
            ]
            
        ]);
        $url = 'https://api.openstreetmap.org/api/0.6/way/1/full.json';
        $mock = $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($url,$return) {
            $mock->shouldReceive('exec')
                    ->once()
                    ->with($url)
                    ->andReturn($return);
        });
        $osmp = app(OsmServiceProvider::class);
        $this->expectException(OsmServiceProviderExceptionNoTags::class);
        $osmp->getPropertiesAndGeometry($osmid);
        $this->assertTrue(false);
    }

    /** @test */
    public function no_nodes_throw_exception() {
        $osmid = 'way/1';
        $return = json_encode([
            'version'=>'0.6',
            'elements'=> [
                [
                    'id' => 2,
                    'type' => 'node',
                    'lat' => 44,
                    'lon' => 10,
                ],
                [
                    'id' => 3,
                    'type' => 'node',
                    'lat' => 45,
                    'lon' => 11,
                ],
                [
                    'id' => 1,
                    'type' => 'way',
                    'tags' => [
                        'name' => 'name of way'
                    ]
                ],
            ]
            
        ]);
        $url = 'https://api.openstreetmap.org/api/0.6/way/1/full.json';
        $mock = $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($url,$return) {
            $mock->shouldReceive('exec')
                    ->once()
                    ->with($url)
                    ->andReturn($return);
        });
        $osmp = app(OsmServiceProvider::class);
        $this->expectException(OsmServiceProviderExceptionWayHasNoNodes::class);
        $osmp->getPropertiesAndGeometry($osmid);
        $this->assertTrue(false);
    }

    /** @test */
    public function no_lon_throw_exception() {
        $osmid = 'way/1';
        $return = json_encode([
            'version'=>'0.6',
            'elements'=> [
                [
                    'id' => 2,
                    'type' => 'node',
                    'lat' => 44,
                ],
                [
                    'id' => 3,
                    'type' => 'node',
                    'lat' => 45,
                    'lon' => 11,
                ],
                [
                    'id' => 1,
                    'type' => 'way',
                    'tags' => [
                        'name' => 'name of way'
                    ],
                    'nodes' => [
                        2,
                        3
                    ]
                ],
            ]
            
        ]);
        $url = 'https://api.openstreetmap.org/api/0.6/way/1/full.json';
        $mock = $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($url,$return) {
            $mock->shouldReceive('exec')
                    ->once()
                    ->with($url)
                    ->andReturn($return);
        });
        $osmp = app(OsmServiceProvider::class);
        $this->expectException(OsmServiceProviderExceptionNodeHasNoLon::class);
        $osmp->getPropertiesAndGeometry($osmid);
        $this->assertTrue(false);
    }
    /** @test */
    public function no_lat_throw_exception() {
        $osmid = 'way/1';
        $return = json_encode([
            'version'=>'0.6',
            'elements'=> [
                [
                    'id' => 2,
                    'type' => 'node',
                    'lon' => 10,
                    'timestamp' => '2020-09-13T14:57:20Z'
                ],
                [
                    'id' => 3,
                    'type' => 'node',
                    'lat' => 45,
                    'lon' => 11,
                    'timestamp' => '2021-09-13T14:57:20Z'
                ],
                [
                    'id' => 1,
                    'type' => 'way',
                    'timestamp' => '2018-09-13T14:57:20Z',
                    'tags' => [
                        'name' => 'name of way'
                    ],
                    'nodes' => [
                        2,
                        3
                    ]
                ],
            ]
            
        ]);
        $url = 'https://api.openstreetmap.org/api/0.6/way/1/full.json';
        $mock = $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($url,$return) {
            $mock->shouldReceive('exec')
                    ->once()
                    ->with($url)
                    ->andReturn($return);
        });
        $osmp = app(OsmServiceProvider::class);
        $this->expectException(OsmServiceProviderExceptionNodeHasNoLat::class);
        $osmp->getPropertiesAndGeometry($osmid);
        $this->assertTrue(false);
    }

    // Positive test
    /** @test */
    public function with_proper_json_has_proper_properties_and_geometry() {
        $osmid = 'way/1';
        $return = $this->getJsonWay();
        $url = 'https://api.openstreetmap.org/api/0.6/way/1/full.json';
        $mock = $this->mock(CurlServiceProvider::class, function (MockInterface $mock) use ($url,$return) {
            $mock->shouldReceive('exec')
                 ->once()
                 ->with($url)
                 ->andReturn($return);
        });
        $osmp = app(OsmServiceProvider::class);
        $result = $osmp->getPropertiesAndGeometry($osmid);

        $expected = [
            [
                'name' => 'Name of way with id 1',
                '_updated_at' => '2021-09-13 14:57:20'
            ],
            [
                'type' => 'LineString',
                'coordinates' => [
                    [10,44],
                    [11,45]
                ]
            ]
        ];

        // TODO: add updated_at test
        $this->assertEquals($expected,$result);

    }


}
