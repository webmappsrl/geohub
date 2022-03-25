<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Layer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppConfigApiHasLayersSectionTest extends TestCase
{
    use RefreshDatabase;

    public function init_features() {
        $this->a = App::factory()->create(['api'=>'webmapp']);
        $this->l1 = Layer::factory()->create(['app_id'=>$this->a->id]);
        $this->l2 = Layer::factory()->create(['app_id'=>$this->a->id]);
    }

    /** @test    */
    public function simple_check() {
        $this->init_features();
        $this->assertEquals('App\Models\App',get_class($this->a));
        $this->assertEquals('App\Models\Layer',get_class($this->l1));
        $this->assertEquals('App\Models\Layer',get_class($this->l2));
    }

    /** @test    */
    public function when_app_has_layer_then_config_api_has_layers_section() {
        $this->init_features();
        $j = $this->get('/api/app/webapp/'.$this->a->id.'/config')->json();
        $this->assertArrayHasKey('MAP',$j);
        $this->assertArrayHasKey('layers',$j['MAP']);
    }
    /** @test    */
    public function when_app_has_layer_then_layers_section_has_two_elements() {
        $this->init_features();
        $j = $this->get('/api/app/webapp/'.$this->a->id.'/config')->json();
        $layers = $j['MAP']['layers'];
        $this->assertEquals(2,count($layers));
    }
    /** @test    */
    public function when_app_has_layer_then_layers_section_has_proper_main_tab_values() {
        $this->init_features();
        $j = $this->get('/api/app/webapp/'.$this->a->id.'/config')->json();
        $layers = $j['MAP']['layers'];
        foreach($layers as $layer) {
            $this->assertTrue(in_array($layer['id'],[$this->l1->id,$this->l2->id]));
            switch ($layer['id']) {
                case $this->l1->id:
                    $actual_layer = $this->l1;
                    break;
                case $this->l2->id:
                    $actual_layer = $this->l2;
                    break;    
                }
            $main_fields = ['id','name','title','subtitle','description'];
            foreach($main_fields as $field) {
                $this->assertEquals($actual_layer->$field,$layer[$field]);
            }
        }
    }
    /** @test    */
    public function when_app_has_layer_then_layers_section_has_proper_behaviour_tab_values() {
        $this->init_features();
        $j = $this->get('/api/app/webapp/'.$this->a->id.'/config')->json();
        $layers = $j['MAP']['layers'];
        foreach($layers as $layer) {
            $this->assertTrue(in_array($layer['id'],[$this->l1->id,$this->l2->id]));
            switch ($layer['id']) {
                case $this->l1->id:
                    $actual_layer = $this->l1;
                    break;
                case $this->l2->id:
                    $actual_layer = $this->l2;
                    break;    
                }
            $behaviour_fields = ['noDetails','noInteraction','minZoom','maxZoom','preventFilter','invertPolygons','alert','show_label'];
            foreach($behaviour_fields as $field) {
                $this->assertEquals($actual_layer->$field,$layer['behaviour'][$field]);
            }
        }
    }
    /** @test    */
    public function when_app_has_layer_then_layers_section_has_proper_style_tab_values() {
        $this->init_features();
        $j = $this->get('/api/app/webapp/'.$this->a->id.'/config')->json();
        $layers = $j['MAP']['layers'];
        foreach($layers as $layer) {
            $this->assertTrue(in_array($layer['id'],[$this->l1->id,$this->l2->id]));
            switch ($layer['id']) {
                case $this->l1->id:
                    $actual_layer = $this->l1;
                    break;
                case $this->l2->id:
                    $actual_layer = $this->l2;
                    break;    
                }
            $style_fields = ['color','fill_color','fill_opacity','stroke_width','stroke_opacity','zindex','line_dash'];
            foreach($style_fields as $field) {
                $this->assertEquals($actual_layer->$field,$layer['style'][$field]);
            }
        }
    }

    /** @test    */
    public function when_app_has_layer_then_layers_section_has_no_invalid_fields() {
        $this->init_features();
        $j = $this->get('/api/app/webapp/'.$this->a->id.'/config')->json();
        $layers = $j['MAP']['layers'];
        foreach($layers as $layer) {
            $invalid_fields = ['created_at','updated_at','app_id'];
            foreach($invalid_fields as $field) {
                $this->assertArrayNotHasKey($field,$layer);
            }
        }
    }
}
