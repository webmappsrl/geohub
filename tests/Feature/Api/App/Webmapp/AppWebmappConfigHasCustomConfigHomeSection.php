<?php

namespace Tests\Feature\Api\App\Webmapp;

use App\Models\App;
use App\Models\Layer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppWebmappConfigHasCustomConfigHomeSection extends TestCase
{
    
    use RefreshDatabase;

    /** @test */
    public function when_has_config_home_set_home_section_is_taken_from_there()
    {
        $app = App::factory()->
          create([
              'api' => 'webmapp',
              'config_home'=>$this->getConfigHome()
            ]);
        $l1 = Layer::factory()->create(['app_id'=>$app->id]);
        $l1->rank=3;$l1->save();

        $l2 = Layer::factory()->create(['app_id'=>$app->id]);
        $l2->rank=2;$l2->save();

        $l3 = Layer::factory()->create(['app_id'=>$app->id]);
        $l3->rank=1;$l3->save();

        $result = $this->getJson('/api/app/webmapp/' . $app->id . '/config.json', []);
        $this->assertEquals(200, $result->getStatusCode());

        $json = json_decode($result->getContent());

        $this->assertTrue(isset($json->HOME));
        $this->assertIsArray($json->HOME);
        $title = $json->HOME[0];
        $this->assertEquals('title',$json->HOME[0]->box_type);
        $this->assertEquals('Parco Maremma APP - testing',$json->HOME[0]->title);

    }

    private function getConfigHome() {
        $string = <<<EOF
        {
            "HOME": [
              {
                "box_type": "title",
                "title": "Parco Maremma APP - testing"
              },
              {
                "box_type": "layer",
                "title": "In Bicicletta",
                "layer": 14
              },
              {
                "box_type": "layer",
                "title": "A Piedi",
                "layer": 13
              },
              {
                "box_type": "base",
                "title": "itinerari",
                "items": [
                  {
                    "title": "T3 Poggio Raso in bici",
                    "image_url": "https://ecmedia.s3.eu-central-1.amazonaws.com/EcMedia/112.jpg",
                    "track_id": 27
                  },
                  {
                    "title": "T2 Cannelle in bici",
                    "image_url": "https://ecmedia.s3.eu-central-1.amazonaws.com/EcMedia/107.jpg",
                    "track_id": 26
                  },
                  {
                    "title": "Alberese - San Rabano",
                    "image_url": "https://ecmedia.s3.eu-central-1.amazonaws.com/EcMedia/104.jpg",
                    "track_id": 25
                  }
                ]
              }
            ]
          }
        EOF;

        return $string;
    }

}
