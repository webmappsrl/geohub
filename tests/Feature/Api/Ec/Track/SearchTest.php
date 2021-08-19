<?php

namespace Tests\Feature\Api\Ec\Track;

use App\Models\EcTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchTest extends TestCase {
    use RefreshDatabase;

    /**
     * @test
     */
    public function check_empty_search_without_features() {
        $result = $this->getJson('/api/ec/track/search?bbox=10,10,15,15', []);

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
    public function check_empty_search_in_bbox() {
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

        $result = $this->getJson('/api/ec/track/search?bbox=10,10,15,15', []);

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
    public function check_search_return_one_cluster_with_more_features() {
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

        $result = $this->getJson('/api/ec/track/search?bbox=25,0,35,0', []);

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
        $this->assertSame(json_encode([30, 0, 31, 0]), json_encode($json["features"][0]["properties"]["bbox"]));
        $this->assertArrayHasKey("images", $json["features"][0]["properties"]);
        $this->assertIsArray($json["features"][0]["properties"]["images"]);
    }

    /**
     * @test
     */
    public function check_search_return_less_than_five_cluster_with_three_hypothetical_clusters() {
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

        $result = $this->getJson('/api/ec/track/search?bbox=0,0,12,0', []);

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
    public function check_search_return_five_cluster_with_more_hypothetical_clusters() {
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

        $result = $this->getJson('/api/ec/track/search?bbox=0,0,27,0', []);

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
