<?php

namespace Tests\Feature\Api\App;

use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppConfigHasMapSectionTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     *
     * @return void
     */
    public function it_has_proper_map_options() {
        $app = App::factory()->create();

        $conf=$app->config();
        $this->assertEquals($app->start_end_icons_show,$conf['MAP']['start_end_icons_show']);
        $this->assertEquals($app->start_end_icons_min_zoom,$conf['MAP']['start_end_icons_min_zoom']);
        $this->assertEquals($app->ref_on_track_show,$conf['MAP']['ref_on_track_show']);
        $this->assertEquals($app->ref_on_track_min_zoom,$conf['MAP']['ref_on_track_min_zoom']);
    }
}
