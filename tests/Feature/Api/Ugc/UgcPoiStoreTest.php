<?php

namespace Tests\Feature\Ugc;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UgcPoiTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUgcPoiStore()
    {
        $this->actingAs(User::where('email', '=', 'team@webmapp.it')->first(), 'api');

        $geometry = DB::raw("(ST_GeomFromText('POINT(10 43)'))");
        $data = [
            'name' => 'TestUgc Poi',
            'app_id' => 'it.webmapp.test',
            'geometry' => $geometry,
        ];

        $response = $this->post(route("api.ugc.poi.store", $data));
        $response->assertStatus(201);
        
    }
}
