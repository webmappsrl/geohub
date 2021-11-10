<?php

namespace Tests\Feature\Api\Ec\Track;

use App\Models\App;
use App\Models\EcTrack;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SearchTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // To prevent the service to post to hoqu for real
        $this->mock(HoquServiceProvider::class, function ($mock) {
            $mock->shouldReceive('store')
                ->andReturn(201);
        });
    }

    public function test_with_no_parameters(): void {
        $result = $this->getJson('/api/ec/track/search', []);
        $result->assertStatus(400);
    }

    public function test_with_missing_bbox_param(): void {
        $result = $this->getJson('/api/ec/track/search?app_id=it.webmapp.webmapp', []);
        $result->assertStatus(400);
    }

    public function test_with_missing_app_id_param(): void {
        $result = $this->getJson('/api/ec/track/search?bbox=0,0,15,15', []);
        $result->assertStatus(400);
    }

    public function test_with_webmapp_app_id_param(): void {
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromText('LINESTRING(0 0 0, 1 1 0, 2 2 0)')")
        ])->count(3)->create();
        $result = $this->getJson('/api/ec/track/search?bbox=0,0,15,15&app_id=it.webmapp.webmapp');

        $result->assertStatus(200);
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(1, $json["features"]);
        $this->assertIsArray($json["features"][0]);
        $this->assertArrayHasKey("properties", $json["features"][0]);
        $this->assertIsArray($json["features"][0]["properties"]);
        $this->assertArrayHasKey("ids", $json["features"][0]["properties"]);
        $this->assertCount(3, $json["features"][0]["properties"]["ids"]);
    }

    public function test_with_custom_app_id_param(): void {
        $app = App::factory([
            'app_id' => 'it.webmapp.test'
        ])->create();
        $track = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromText('LINESTRING(0 0 0, 1 1 0, 2 2 0)')")
        ])->create();
        $user = User::factory()->create();
        $track->author()->associate($user);
        $track->save();
        $app->author()->associate($user);
        $app->save();

        $tracks = EcTrack::factory([
            'geometry' => DB::raw("ST_GeomFromText('LINESTRING(0 0 0, 1 1 0, 2 2 0)')")
        ])->count(3)->create();

        // Make sure the tracks are not all owned by the same user
        $user = User::where('email', '=', 'team@webmapp.it')->first();
        foreach ($tracks as $track) {
            $track->author()->associate($user);
            $track->save();
        }

        $result = $this->getJson('/api/ec/track/search?bbox=0,0,15,15&app_id=it.webmapp.test');

        $result->assertStatus(200);
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(1, $json["features"]);
        $this->assertIsArray($json["features"][0]);
        $this->assertArrayHasKey("properties", $json["features"][0]);
        $this->assertIsArray($json["features"][0]["properties"]);
        $this->assertArrayHasKey("ids", $json["features"][0]["properties"]);
        $this->assertCount(1, $json["features"][0]["properties"]["ids"]);
    }

    public function test_with_unknown_app_id_param(): void {
        $result = $this->getJson('/api/ec/track/search?bbox=0,0,15,15&app_id=it.webmapp.test');

        $result->assertStatus(400);
    }

    public function test_empty_search_without_features() {
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $result = $this->getJson('/api/ec/track/search?bbox=10,10,15,15&app_id=it.webmapp.webmapp', []);

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

    public function test_empty_search_in_bbox() {
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [0, 0, 0],
                [1, 1, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);

        $result = $this->getJson('/api/ec/track/search?bbox=10,10,15,15&app_id=it.webmapp.webmapp', []);

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

    public function test_search_return_one_cluster_with_more_features() {
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [0, 0, 0],
                [1, 1, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [30, 0, 0],
                [31, 0, 0]
            ]
        ]);
        EcTrack::factory(1)->create([
            'id' => 10000,
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        EcTrack::factory(1)->create([
            'id' => 10001,
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);

        $result = $this->getJson('/api/ec/track/search?bbox=25,0,35,0&app_id=it.webmapp.webmapp', []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(1, $json["features"]);
        $this->assertIsArray($json["features"][0]);
        $this->assertArrayHasKey("type", $json["features"][0]);
        $this->assertIsString($json["features"][0]["type"]);
        $this->assertSame("Feature", $json["features"][0]["type"]);
        $this->assertArrayHasKey("geometry", $json["features"][0]);
        $this->assertIsArray($json["features"][0]["geometry"]);
        $this->assertArrayHasKey("type", $json["features"][0]["geometry"]);
        $this->assertSame("Point", $json["features"][0]["geometry"]["type"]);
        $this->assertArrayHasKey("coordinates", $json["features"][0]["geometry"]);
        $this->assertIsArray($json["features"][0]["geometry"]["coordinates"]);
        $this->assertSame(json_encode([30.5, 0]), json_encode($json["features"][0]["geometry"]["coordinates"]));
        $this->assertArrayHasKey("properties", $json["features"][0]);
        $this->assertIsArray($json["features"][0]["properties"]);
        $this->assertArrayHasKey("ids", $json["features"][0]["properties"]);
        $this->assertIsArray($json["features"][0]["properties"]["ids"]);
        $this->assertTrue(in_array(10000, $json["features"][0]["properties"]["ids"]));
        $this->assertCount(2, $json["features"][0]["properties"]["ids"]);
        $this->assertTrue(in_array(10001, $json["features"][0]["properties"]["ids"]));
        $this->assertArrayHasKey("bbox", $json["features"][0]["properties"]);
        $this->assertIsArray($json["features"][0]["properties"]["bbox"]);
        $this->assertCount(4, $json["features"][0]["properties"]["bbox"]);
        /*
         * The bbox is calculated as the bbox including the centroids.
         * As the tracks are equal the bbox must be the track centroid
         */
        $this->assertSame(json_encode([30.5, 0, 30.5, 0]), json_encode($json["features"][0]["properties"]["bbox"]));
        $this->assertArrayHasKey("images", $json["features"][0]["properties"]);
        $this->assertIsArray($json["features"][0]["properties"]["images"]);
    }

    public function test_search_return_less_than_five_cluster_with_three_hypothetical_clusters() {
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [0, 0, 0],
                [1, 1, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [5, 0, 0],
                [6, 0, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [10, 0, 0],
                [11, 0, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);

        $result = $this->getJson('/api/ec/track/search?bbox=0,0,12,0&app_id=it.webmapp.webmapp', []);

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

    public function test_search_return_six_cluster() {
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [0, 0, 0],
                [1, 1, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [5, 0, 0],
                [6, 0, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [10, 0, 0],
                [11, 0, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [15, 0, 0],
                [16, 0, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [20, 0, 0],
                [21, 0, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [25, 0, 0],
                [26, 0, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);

        $result = $this->getJson('/api/ec/track/search?bbox=0,0,27,0&app_id=it.webmapp.webmapp', []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(6, $json["features"]);
    }

    public function test_search_on_reference_track_with_no_close_tracks() {
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [0, 0, 0],
                [1, 1, 0]
            ]
        ]);
        $track = EcTrack::factory()->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $trackId = $track->id;
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [5, 0, 0],
                [6, 0, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);

        $result = $this->getJson("/api/ec/track/search?bbox=0,0,27,0&reference_id=$trackId&app_id=it.webmapp.webmapp", []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(1, $json["features"]);
        $this->assertIsArray($json["features"][0]);
        $this->assertArrayHasKey("properties", $json["features"][0]);
        $this->assertIsArray($json["features"][0]["properties"]);
        $this->assertArrayHasKey("ids", $json["features"][0]["properties"]);
        $this->assertIsArray($json["features"][0]["properties"]["ids"]);
        $this->assertCount(1, $json["features"][0]["properties"]["ids"]);
        $this->assertTrue(in_array($trackId, $json["features"][0]["properties"]["ids"]));
    }

    public function test_search_on_reference_track_with_two_close_tracks_in_the_same_cluster() {
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [0, 0, 0],
                [1, 0, 0]
            ]
        ]);
        $track = EcTrack::factory()->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $trackId = $track->id;
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [0.5, -1, 0],
                [0.5, 1, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);

        $result = $this->getJson("/api/ec/track/search?bbox=0,0,27,5&reference_id=$trackId&app_id=it.webmapp.webmapp", []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(1, $json["features"]);
        $this->assertIsArray($json["features"][0]);
        $this->assertArrayHasKey("properties", $json["features"][0]);
        $this->assertIsArray($json["features"][0]["properties"]);
        $this->assertArrayHasKey("ids", $json["features"][0]["properties"]);
        $this->assertIsArray($json["features"][0]["properties"]["ids"]);
        $this->assertCount(3, $json["features"][0]["properties"]["ids"]);
        $this->assertTrue(in_array($trackId, $json["features"][0]["properties"]["ids"]));
    }

    public function test_search_on_reference_track_with_two_close_tracks_in_another_cluster() {
        App::factory([
            'app_id' => 'it.webmapp.webmapp'
        ])->create();
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [0, 0, 0],
                [1, 0, 0]
            ]
        ]);
        $track = EcTrack::factory()->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);
        $trackId = $track->id;
        $geometry = json_encode([
            "type" => "LineString",
            "coordinates" => [
                [0.5, -1.5, 0],
                [0.5, 0.5, 0]
            ]
        ]);
        EcTrack::factory(2)->create([
            'geometry' => DB::raw("ST_GeomFromGeojson('$geometry')")
        ]);

        $result = $this->getJson("/api/ec/track/search?bbox=0,0,27,2&reference_id=$trackId&app_id=it.webmapp.webmapp", []);

        $this->assertEquals(200, $result->getStatusCode());
        $json = $result->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey("type", $json);
        $this->assertIsString($json["type"]);
        $this->assertSame("FeatureCollection", $json["type"]);
        $this->assertArrayHasKey("features", $json);
        $this->assertIsArray($json["features"]);
        $this->assertCount(2, $json["features"]);
        $this->assertIsArray($json["features"][0]);
        $this->assertArrayHasKey("properties", $json["features"][0]);
        $this->assertIsArray($json["features"][0]["properties"]);
        $this->assertArrayHasKey("ids", $json["features"][0]["properties"]);
        $this->assertIsArray($json["features"][0]["properties"]["ids"]);
        $this->assertIsArray($json["features"][1]);
        $this->assertArrayHasKey("properties", $json["features"][1]);
        $this->assertIsArray($json["features"][1]["properties"]);
        $this->assertArrayHasKey("ids", $json["features"][1]["properties"]);
        $this->assertIsArray($json["features"][1]["properties"]["ids"]);
        $ids = array_merge($json["features"][0]["properties"]["ids"], $json["features"][1]["properties"]["ids"]);
        $this->assertCount(3, $ids);
        $this->assertTrue(in_array($trackId, $ids));
    }
}
