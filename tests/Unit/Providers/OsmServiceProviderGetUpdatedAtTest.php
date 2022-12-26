<?php

namespace Tests\Unit\Providers;
use App\Providers\OsmServiceProvider;
use Exception;
use Tests\TestCase;

class OsmServiceProviderGetUpdatedAtTest extends TestCase
{
    // Exceptions
    /** @test */
    public function no_elements_throw_Exception() {
        $osmp = app(OsmServiceProvider::class);
        $json = [];
        $this->expectException(Exception::class);
        $osmp->getUpdatedAt($json);
    } 
    /** @test */
    public function no_timestamp_throw_Exception() {
        $osmp = app(OsmServiceProvider::class);
        $json = [
            'elements' => [
                [
                    'type' => 'node',
                ]
            ]
        ];
        $this->expectException(Exception::class);
        $osmp->getUpdatedAt($json);

        $json = [
            'elements' => [
                [
                    'type' => 'node',
                ],
                [
                    'type' => 'node',
                    'timestamp' => '2000-01-01T12:30:30Z',
                ],
            ]
        ];
        $this->expectException(Exception::class);
        $osmp->getUpdatedAt($json);
    }  

    // NODE
    /** @test */
    public function with_node_it_returns_timestamp() {
        $this->assertTrue(false);
    }  

    // WAY
    /** @test */
    public function with_way_with_older_node_it_returns_way_timestamp() {
        $this->assertTrue(false);
    }  

    /** @test */
    public function with_way_with_older_way_it_returns_node_timestamp() {
        $this->assertTrue(false);
    }  

    // RELATION
    /** @test */
    public function with_relation_with_relation_more_recent_it_returns_relation_timestamp() {
        $this->assertTrue(false);
    }  
    /** @test */
    public function with_relation_with_way_more_recent_it_returns_way_timestamp() {
        $this->assertTrue(false);
    }  
    /** @test */
    public function with_relation_with_node_more_recent_it_returns_node_timestamp() {
        $this->assertTrue(false);
    }  
}
