<?php

namespace Tests\Feature\Api\Ec\Track;

use App\Models\App;
use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MostViewedTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    /**
     * @test
     */
    public function check_api_works() {
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $result = $this->getJson('/api/ec/track/most_viewed?app_id=it.webmapp.webmapp', []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(0, $json["features"]);
    }

    /**
     * @test
     */
    public function check_response_has_three_tracks_when_only_three_available() {
        EcTrack::factory(3)->create();
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $result = $this->getJson('/api/ec/track/most_viewed?app_id=it.webmapp.webmapp', []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(3, $json["features"]);
    }

    /**
     * @test
     */
    public function check_response_has_five_tracks_when_more_are_available() {
        EcTrack::factory(10)->create();

        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $result = $this->getJson('/api/ec/track/most_viewed?app_id=it.webmapp.webmapp', []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(5, $json["features"]);
    }
}
