<?php

namespace Tests\Unit\Providers;

use App\Providers\OsmServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OsmServiceProvidergetFullOsmApiUrlByOsmIdTest extends TestCase
{
    public function test_with_node()
    {
        $osmp = app(OsmServiceProvider::class);

        $osmid = 'node/1234';
        $url = 'https://api.openstreetmap.org/api/0.6/'.$osmid.'.json';
        $this->assertEquals($url,$osmp->getFullOsmApiUrlByOsmId($osmid));
    }
    public function test_with_way()
    {
        $osmp = app(OsmServiceProvider::class);

        $osmid = 'way/1234';
        $url = 'https://api.openstreetmap.org/api/0.6/'.$osmid.'/full.json';
        $this->assertEquals($url,$osmp->getFullOsmApiUrlByOsmId($osmid));

    }
    public function test_with_relation()
    {
        $osmp = app(OsmServiceProvider::class);

        $osmid = 'relation/1234';
        $url = 'https://api.openstreetmap.org/api/0.6/'.$osmid.'/full.json';
        $this->assertEquals($url,$osmp->getFullOsmApiUrlByOsmId($osmid));
    }
}
