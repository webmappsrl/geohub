<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Layer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppConfigHomeSectionTest extends TestCase
{
    /** @test */
    public function config_has_home_section() {
        $app = App::factory()->create(['api'=>'webapp']);
        $j = $this->get('/api/app/webapp/'.$app->id.'/config')->json();
        $this->assertArrayHasKey('HOME',$j);
    }

    /** @test */
    public function home_section_has_view_title() {
        $app = App::factory()->create(['api'=>'webapp']);
        $j = $this->get('/api/app/webapp/'.$app->id.'/config')->json();
        $home = $j['HOME'];
        $this->assertIsArray($home);
        $views=[];
        $title='';
        foreach($home as $item) {
            $this->assertArrayHasKey('view',$item);
            $views[]=$item['view'];
            if($item['view']=='title') {
                $title = $item['title'];
            }
        }
        $this->assertTrue(in_array('title',$views));
        $this->assertEquals($app->name,$title);
    }

    /** @test */
    public function home_section_has_layers_section() {
        $app = App::factory()->create(['api'=>'webapp']);
        $l1 = Layer::factory()->create(['app_id'=>$app->id]);
        $l2 = Layer::factory()->create(['app_id'=>$app->id]);

        $j = $this->get('/api/app/webapp/'.$app->id.'/config')->json();
        $home = $j['HOME'];
        $this->assertIsArray($home);
        $views=[];
        foreach($home as $item) {
            $views[]=$item['view'];
            if($item['view']=='compact-horizontal'){
                if($item['terms'][0]==$l1->id) {
                    $this->assertEquals($l1->title,$item['title']);
                } else {
                    $this->assertEquals($l2->title,$item['title']);
                }
            }
        }
        $this->assertTrue(in_array('compact-horizontal',$views));
    }
}
