<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Layer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LayerSimpleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Just check relation belongsTo
     *
     * @return void
     * @test
     */
    public function layer_belongs_to_app() {
        $l = Layer::factory()->create();
        $this->assertEquals('App\Models\App',get_class($l->app));
    }
    /**
     * Just check relation hasMany
     *
     * @return void
     * @test
     */
    public function app_has_many_layers() {
        $app = App::factory()->create();
        Layer::factory(2)->create(['app_id'=>$app->id]);
        $this->assertEquals(2,$app->layers->count());
        foreach($app->layers as $layer) {
            $this->assertEquals('App\Models\Layer',get_class($layer));
        }
    }
}
