<?php

namespace Tests\Unit\Providers;

use App\Providers\OsmServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OsmServiceProviderCheckOsmIdTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_some_cases() 
    {
        $osmp = app(OsmServiceProvider::class);
        $this->assertTrue($osmp->checkOsmId('node/1234'));
        $this->assertTrue($osmp->checkOsmId('way/1234'));
        $this->assertTrue($osmp->checkOsmId('relation/1234'));

        $this->assertFalse($osmp->checkOsmId('node/1234a'));
        $this->assertFalse($osmp->checkOsmId('way/1234a'));
        $this->assertFalse($osmp->checkOsmId('relation/1234a'));

        $this->assertFalse($osmp->checkOsmId('xxx/1234'));
    }
}
