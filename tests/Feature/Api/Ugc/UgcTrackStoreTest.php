<?php

namespace Tests\Feature\Ugc;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UgcTrackTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUgcTrackStore()
    {
        $user = User::where('email', '=', 'team@webmapp.it')->first();
        $this->actingAs($user, 'api');
        $geometry = [
            "type" => "LineString",
            "coordinates" => [[10, 44], [11, 44], [11, 43], [10, 43]]
        ];
        $value = "(ST_GeomFromText('LINESTRING({$geometry['coordinates'][0][0]} {$geometry['coordinates'][0][1]}, {$geometry['coordinates'][1][0]} {$geometry['coordinates'][1][1]}, {$geometry['coordinates'][2][0]} {$geometry['coordinates'][2][1]}, {$geometry['coordinates'][3][0]} {$geometry['coordinates'][3][1]})'))";

        $data = [
            'user_id' => $user->id,
            'app_id' => 'it.webmapp.test',
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'geometry' => $geometry,
        ];

        $response = $this->postJson(route("api.ugc.track.store", $data));
        $content = $response->getContent();
        $response->assertStatus(201);
        $this->assertJson($content);

        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        $this->assertIsInt($json['data']['id']);
        $this->assertEquals($user->id, $json['data']['user_id']);
        $this->assertEquals($data['app_id'], $json['data']['app_id']);
        $this->assertEquals($data['name'], $json['data']['name']);
        $this->assertEquals($data['description'], $json['data']['description']);
        $this->assertEquals($value, $json['data']['geometry']);
    }
}
