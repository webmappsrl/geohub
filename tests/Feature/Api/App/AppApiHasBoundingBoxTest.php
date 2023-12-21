<?php

namespace Tests\Feature;

use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppApiHasBoundingBoxTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     *
     * @test
     */
    public function when_bbox_exist_then_config_api_has_bbox()
    {
        $bbox_test = json_encode([1, 2, 3, 4]);
        $app = App::factory()->create(['api' => 'webapp', 'map_bbox' => $bbox_test]);
        Storage::disk('conf')->delete($app->id.'.json');
        $config_response = $this->getJson('/api/app/webapp/'.$app->id.'/config', []);
        $config_json = json_decode($config_response->getContent(), true);

        $this->assertTrue(isset($config_json['MAP']));
        $this->assertTrue(isset($config_json['MAP']['bbox']));

        $this->assertEquals(1, $config_json['MAP']['bbox'][0]);
        $this->assertEquals(2, $config_json['MAP']['bbox'][1]);
        $this->assertEquals(3, $config_json['MAP']['bbox'][2]);
        $this->assertEquals(4, $config_json['MAP']['bbox'][3]);
    }
}
