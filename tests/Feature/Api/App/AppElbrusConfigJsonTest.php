<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\App;
use Tests\TestCase;

class AppElbrusConfigJsonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 404 with not found app onject
     */
    public function testNoIdReturnCode404()
    {
        $result = $this->getJson('/api/app/elbrus/0/config.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    /**
     * Test code 200 for existing app
     */
    public function testExistingAppReturns200()
    {
        $app=App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());

    }

    /**
     * Test name, id, customerName API
     * config.json example https://k.webmapp.it/caipontedera/config.json
     */
    public function testSectionApp()
    {
        $app=App::factory()->create();
        $result = $this->getJson('/api/app/elbrus/'.$app->id.'/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->APP));
        $this->assertEquals($app->app_id,$json->APP->id);
        $this->assertEquals($app->name,$json->APP->name);
        $this->assertEquals($app->customerName,$json->APP->customerName);

    }
}
