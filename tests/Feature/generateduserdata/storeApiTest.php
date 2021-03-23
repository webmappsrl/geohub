<?php

namespace Tests\Feature\generateduserdata;

use App\Models\UserGeneratedData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class storeApiTest extends TestCase
{

    use RefreshDatabase;

    public function testWithNoData()
    {
        $count = count(UserGeneratedData::get());
        $response = $this->post('/api/usergenerateddata/store', []);
        $this->assertSame($response->status(), 422);
        $this->assertCount($count, UserGeneratedData::get());
    }

    public function testWithAPoi()
    {
        $appId = 'it.webmapp.test';
        $formData = [
            "name" => "Test name"
        ];
        $data = [
            "type" => "FeatureCollection",
            "features" => [
                [
                    "type" => "Feature",
                    "geometry" => [
                        "type" => "Point",
                        "coordinates" => [10, 20]
                    ],
                    "properties" => [
                        "app" => [
                            "id" => $appId
                        ],
                        "form_data" => $formData
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/usergenerateddata/store', $data);
        $this->assertSame($response->status(), 201);

        $this->assertCount(1, UserGeneratedData::get());
        $newContent = UserGeneratedData::first();
        $this->assertSame($appId, $newContent->app_id);
        $this->assertSame(json_encode($formData), json_encode(json_decode($newContent->raw_data, true)));
    }

    public function testWithATrack()
    {
        $appId = 'it.webmapp.test';
        $formData = [
            "name" => "Test name"
        ];
        $data = [
            "type" => "FeatureCollection",
            "features" => [
                [
                    "type" => "Feature",
                    "geometry" => [
                        "type" => "LineString",
                        "coordinates" => [[10, 20], [10, 20], [10, 20]]
                    ],
                    "properties" => [
                        "app" => [
                            "id" => $appId
                        ],
                        "form_data" => $formData
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/usergenerateddata/store', $data);
        $response->assertStatus(201);

        $this->assertCount(1, UserGeneratedData::get());
        $newContent = UserGeneratedData::first();
        $this->assertSame($appId, $newContent->app_id);
        $this->assertSame(json_encode($formData), json_encode(json_decode($newContent->raw_data, true)));
    }
}
