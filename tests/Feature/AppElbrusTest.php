<?php

namespace Tests\Feature;

use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppElbrusTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     * A basic feature test example.
     *
     * @return void
     */
    public function check_if_exists_external_overlays_field()
    {
        $external_overlays = <<<EXTERNAL_OVERLAYS
[
    {
        "id": "punti_acqua",
        "type": "geojson",
        "geojsonUrl": "punti_acqua.geojson",
        "color": "#3EAFE3",
        "icon": "wm-icon-waterdrop",
        "noDetails": false,
        "name": "Punti acqua",
        "locale": "it",
        "createTaxonomy": "webmapp_category"
        },
        {
        "id": "segnaletica_verticale",
        "type": "geojson",
        "geojsonUrl": "segnaletica_verticale.geojson",
        "color": "#e67300",
        "icon": "wm-icon-guidepost-15",
        "noDetails": false,
        "name": "Segnaletica verticale",
        "locale": "it",
        "createTaxonomy": "webmapp_category"
    }
]
EXTERNAL_OVERLAYS;

        $app = App::factory()->create([
            'external_overlays' => $external_overlays
        ]);
        $json = json_encode($external_overlays);
        $this->assertIsObject($app);
        $this->assertJson($json, $app->external_overlays);
    }
}
