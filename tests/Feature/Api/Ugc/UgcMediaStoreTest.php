<?php

namespace Tests\Feature\Ugc;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UgcMediaTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUgcMediaStore()
    {
        $user = User::where('email', '=', 'team@webmapp.it')->first();
        $this->actingAs($user, 'api');
        $geometry = [
            "type" => "Point",
            "coordinates" => [10, 44]
        ];
        $value = "(ST_GeomFromText('POINT({$geometry['coordinates'][0]} {$geometry['coordinates'][1]})'))";

        $image = base_path() . '/tests/Fixtures/EcMedia/test.jpg';
        $content = file_get_contents($image);
        $exif = exif_read_data($image);

        $app_id = 'it.webmapp.test';
        $data = [
            'user_id' => $user->id,
            'app_id' => $app_id,
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'image' => base64_encode($content),
            'raw_data' => json_encode($exif),
            'geometry' => $geometry,
        ];

        $response = $this->postJson(route("api.ugc.media.store", $data));
        Storage::disk('public')->delete('ugc_media/' . $app_id . '/1');
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
        $this->assertArrayHasKey('relative_url', $json['data']);
        //$this->assertEquals($value, $json['data']['geometry']);
    }
}
