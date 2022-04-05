<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Layer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppWebmappConfigApiHomeLayersOrderTest extends TestCase
{

    use RefreshDatabase;

    /** @test */
    public function when_layers_has_rank_order_in_home_section_follows_rank()
    {
        $app = App::factory()->create(['api' => 'webmapp']);
        $l1 = Layer::factory()->create(['app_id'=>$app->id]);
        $l1->rank=3;$l1->save();

        $l2 = Layer::factory()->create(['app_id'=>$app->id]);
        $l2->rank=2;$l2->save();

        $l3 = Layer::factory()->create(['app_id'=>$app->id]);
        $l3->rank=1;$l3->save();

        $this->assertEquals(3,$l1->rank);
        $this->assertEquals(2,$l2->rank);
        $this->assertEquals(1,$l3->rank);

        $result = $this->getJson('/api/app/webmapp/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());

        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->HOME));
        $this->assertIsArray($json->HOME);
        $title = $json->HOME[0];
        $this->assertEquals($title->view,'title');

        $l1_conf = $json->HOME[1];
        $this->assertEquals($l1_conf->view,'compact-horizontal');
        $this->assertEquals($l3->id,$l1_conf->terms[0]);

        $l2_conf = $json->HOME[2];
        $this->assertEquals($l2->id,$l2_conf->terms[0]);

        $l3_conf = $json->HOME[3];
        $this->assertEquals($l1->id,$l3_conf->terms[0]);

    }

}
