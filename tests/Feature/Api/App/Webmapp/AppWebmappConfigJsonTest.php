<?php

namespace Tests\Feature\Api\App\Webmapp;

use App\Models\App;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppWebmappConfigJsonTest extends TestCase
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

    /**
     * Test 404 with not found app object
     *
     * @test
     */
    public function when_id_is_null_it_returns_404()
    {
        $result = $this->getJson('/api/app/webmapp/0/config.json', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    /**
     * Test code 200 for existing app
     *
     * @test
     */
    public function when_app_it_exists_it_returns_200()
    {
        $app = App::factory()->create(['api' => 'webmapp']);
        $result = $this->getJson('/api/app/webmapp/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
    }

    /**
     * Test name, id, customerName API
     * config.json example https://k.webmapp.it/caipontedera/config.json
     *
     * @test
     */
    public function when_api_is_webmapp_it_returns_proper_section_app()
    {
        $app = App::factory()->create(['api' => 'webmapp']);
        $result = $this->getJson('/api/app/webmapp/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->APP));
        $this->assertEquals($app->app_id, $json->APP->id);
        $this->assertEquals($app->name, $json->APP->name);
        $this->assertEquals($app->customer_name, $json->APP->customerName);
        $this->assertEquals($app->id, $json->APP->geohubId);
    }

    public function test_api_is_webmapp_and_has_record_enabled_it_should_have_geolocation_section()
    {
        $app = App::factory()->create([
            'api' => 'webmapp',
            'geolocation_record_enable' => true
        ]);
        $result = $this->getJson('/api/app/webmapp/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        $this->assertTrue($json->GEOLOCATION->record->enable);
    }
}
