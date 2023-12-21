<?php

namespace Tests\Feature\Api\Ugc;

use App\Models\UgcPoi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UgcPoiIndexTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * @test
     * A basic feature test example.
     *
     * @return void
     */
    public function get_the_ugc_poi_list_for_the_authenticated_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $app_id = 'it.webmapp.test';
        UgcPoi::factory(5)->create([
            'app_id' => $app_id,
            'user_id' => $user1,
        ]);
        UgcPoi::factory(20)->create([
            'app_id' => $app_id,
            'user_id' => $user2,
        ]);

        $this->actingAs($user1, 'api');
        $response = $this->get(route('api.ugc.poi.index', ['app_id' => $app_id]));
        $content = $response->getContent();
        $response->assertStatus(200);
        $this->assertJson($content);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        $this->assertCount(5, $json['data']);

        UgcPoi::factory(15)->create([
            'app_id' => $app_id,
            'user_id' => $user1,
        ]);
        $response = $this->get(route('api.ugc.poi.index', ['app_id' => $app_id, 'page' => 1]));
        $json = $response->json();
        $this->assertCount(10, $json['data']);
        $list10 = $json['data'];

        $response = $this->get(route('api.ugc.poi.index', ['app_id' => $app_id, 'page' => 2]));
        $json = $response->json();
        $this->assertCount(10, $json['data']);
        $list11 = $json['data'];

        $this->assertNotEquals($list11, $list10);
    }
}
