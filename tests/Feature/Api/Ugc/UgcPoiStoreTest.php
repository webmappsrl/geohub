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
        $user = User::where('email', '=', 'team@webmapp.it')->first();
        $this->actingAs($user, 'api');
        $geometry = [
            "type" => "Point",
            "coordinates" => [10, 44]
        ];
        $value = "(ST_GeomFromText('POINT({$geometry['coordinates'][0]} {$geometry['coordinates'][1]})'))";

        $data = [
            'user_id' => $user->id,
            'app_id' => 'it.webmapp.test',
            'name' => 'TestUgc Poi Title',
            'description' => 'TestUgc Poi Description',
            'geometry' => $geometry,
        ];

        $response = $this->postJson(route("api.ugc.poi.store", $data));
        $response->assertStatus(201);
        $content = $response->getContent();
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        $this->assertEquals($user->id, $json['data']['user_id']);
        $this->assertEquals($data['app_id'], $json['data']['app_id']);
        $this->assertEquals($data['name'], $json['data']['name']);
        $this->assertEquals($data['description'], $json['data']['description']);
        $this->assertEquals($value, $json['data']['geometry']);
    }
}
