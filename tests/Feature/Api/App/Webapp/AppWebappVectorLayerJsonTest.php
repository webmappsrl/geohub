<?php

namespace Tests\Feature\Api\App\Webapp;

use App\Models\App;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppWebappVectorLayerJsonTest extends TestCase
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
        $result = $this->getJson('/api/app/webapp/0/vector_layer', []);
        $this->assertEquals(404, $result->getStatusCode());
    }

    /**
     * Test code 200 for existing app
     *
     * @test
     */
    public function when_app_it_exists_it_returns_200()
    {
        $app = App::factory()->create(['api' => 'webapp']);
        $result = $this->getJson('/api/app/webapp/'.$app->id.'/vector_layer', []);
        $this->assertEquals(200, $result->getStatusCode());
    }

    /**
     * Test name, id, customerName API
     * config.json example https://k.webmapp.it/caipontedera/config.json
     *
     * @test
     */
    public function when_api_is_webapp_it_returns_proper_json()
    {
        $app = App::factory()->create(['api' => 'webapp']);
        $result = $this->getJson('/api/app/webapp/'.$app->id.'/vector_layer', []);
        $this->assertEquals(200, $result->getStatusCode());
        $json = json_decode($result->getContent());

        // Checks
        $this->assertTrue(isset($json->tiles));
        $this->assertEquals('https://jidotile.webmapp.it/?x={x}&y={y}&z={z}&index=geohub_app_'.$app->id, $json->tiles[0]);

    }
}
