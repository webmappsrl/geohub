<?php

namespace Tests\Feature;

use App\Helpers\GeoJsonHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GeoJsonHelperTest extends TestCase
{
    /**
     * @test
     * @return boolean
     */
    public function is_geojson_with_feature_point_returns_true() {
        $this->assertTrue(GeoJsonHelper::isGeojson(GeoJsonStubs::getFeaturePoint()));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_geojson_with_feature_coolection_points_returns_true() {
        $this->assertTrue(GeoJsonHelper::isGeojson(GeoJsonStubs::getFeatureCollectionPoints()));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_geojson_feature_with_feature_point_returns_true() {
        $this->assertTrue(GeoJsonHelper::isGeojsonFeature(GeoJsonStubs::getFeaturePoint()));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_geojson_feature_collection_with_feature_point_returns_false() {
        $this->assertFalse(GeoJsonHelper::isGeojsonFeatureCollection(GeoJsonStubs::getFeaturePoint()));
    }
    /**
     * @test
     * @return boolean
     */
    public function is_geojson_feature_with_feature_collection_points_returns_false() {
        $this->assertFalse(GeoJsonHelper::isGeojsonFeature(GeoJsonStubs::getFeatureCollectionPoints()));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_geojson_feature_collection_with_feature_collection_point_returns_tre() {
        $this->assertTrue(GeoJsonHelper::isGeojsonFeatureCollection(GeoJsonStubs::getFeatureCollectionPoints()));
    }

    /**
     * @test
     * @return boolean
     */
    public function convert_collection_to_feature_with_feature_returns_same_feature() {
        $f = GeoJsonStubs::getFeaturePoint();
        $this->assertEquals($f,GeoJsonHelper::convertCollectionToFirstFeature($f));
    }

    /**
     * @test
     * @return boolean
     */
    public function convert_collection_to_feature_with_feature_collection_returns_feature() {
        $c = GeoJsonStubs::getFeatureCollectionPoints();
        $this->assertTrue(GeoJsonHelper::isGeojsonFeature(GeojsonHelper::convertCollectionToFirstFeature($c)));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_polygon_with_feature_polygon_returns_true() {
        $this->assertTrue(GeoJsonHelper::isGeojsonFeaturePolygon(GeoJsonStubs::getFeaturePolygon()));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_polygon_with_feature_point_returns_false() {
        $this->assertFalse(GeoJsonHelper::isGeojsonFeaturePolygon(GeoJsonStubs::getFeaturePoint()));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_polygon_with_feature_collection_points_returns_false() {
        $this->assertFalse(GeoJsonHelper::isGeojsonFeaturePolygon(GeoJsonStubs::getFeatureCollectionPoints()));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_polygon_with_feature_multipolygon_returns_false() {
        $this->assertFalse(GeoJsonHelper::isGeojsonFeaturePolygon(GeoJsonStubs::getFeatureMultiPolygon()));
    }
    /**
     * @test
     * @return boolean
     */
    public function is_multipolygon_with_feature_polygon_returns_false() {
        $this->assertFalse(GeoJsonHelper::isGeojsonFeatureMultiPolygon(GeoJsonStubs::getFeaturePolygon()));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_multipolygon_with_feature_point_returns_false() {
        $this->assertFalse(GeoJsonHelper::isGeojsonFeatureMultiPolygon(GeoJsonStubs::getFeaturePoint()));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_multipolygon_with_feature_collection_points_returns_false() {
        $this->assertFalse(GeoJsonHelper::isGeojsonFeatureMultiPolygon(GeoJsonStubs::getFeatureCollectionPoints()));
    }

    /**
     * @test
     * @return boolean
     */
    public function is_multipolygon_with_feature_multipolygon_returns_true() {
        $this->assertTrue(GeoJsonHelper::isGeojsonFeatureMultiPolygon(GeoJsonStubs::getFeatureMultiPolygon()));
    }

    /**
     * @test
     * @return boolean
     */
    public function convert_polygon_to_multipolygon_with_feature_multipolygon_returns_same() {
        $m = GeoJsonStubs::getFeatureMultiPolygon();
        $this->assertEquals($m,GeoJsonHelper::convertPolygonToMultiPolygon($m));
    }

    /**
     * @test
     * @return boolean
     */
    public function convert_polygon_to_multipolygon_with_feature_polygon_returns_multipolygon() {
        $p = GeoJsonStubs::getFeaturePolygon();
        $this->assertTrue(GeoJsonHelper::isGeojsonFeatureMultiPolygon(GeoJsonHelper::convertPolygonToMultiPolygon($p)));
    }

}

class GeoJsonStubs {
    public static function getFeaturePoint() {
        $geojson = <<<EOF
        {
                "type": "Feature",
                "properties": {
                    "id" : 1
                },
                "geometry": {
                  "type": "Point",
                  "coordinates": [
                    10.401697754859924,
                    43.715550235375034
                  ]
                }
          }
        EOF;
        return $geojson;
    }
    public static function getFeatureCollectionPoints() {
        $geojson = <<<EOF
        {
            "type": "FeatureCollection",
            "features": [
              {
                "type": "Feature",
                "properties": {
                    "id" : 1
                },
                "geometry": {
                  "type": "Point",
                  "coordinates": [
                    10.401697754859924,
                    43.715550235375034
                  ]
                }
              },
              {
                "type": "Feature",
                "properties": {
                    "id" : 2
                },
                "geometry": {
                  "type": "Point",
                  "coordinates": [
                    10.401697754859924,
                    43.715550235375034
                  ]
                }
              }
            ]
          }
        EOF;
        return $geojson;
    }
    public static function getFeaturePolygon() {
        $string = <<<EOF
        {
            "type": "Feature",
            "properties": {
                "id" : 1
            },
            "geometry": {
              "type": "Polygon",
              "coordinates": [
                [
                  [
                    10.40130615234375,
                    43.716031017652014
                  ],
                  [
                    10.440444946289062,
                    43.765143524274066
                  ],
                  [
                    10.36834716796875,
                    43.781505406999756
                  ],
                  [
                    10.40130615234375,
                    43.716031017652014
                  ]
                ]
              ]
            }
          }
        EOF;
        return $string;
    }
    public static function getFeatureMultiPolygon() {
        $string = <<<EOF
        {
            "type": "Feature",
            "properties": {
                "id" : 1
            },
            "geometry": {
              "type": "MultiPolygon",
              "coordinates": [[
                [
                  [
                    10.40130615234375,
                    43.716031017652014
                  ],
                  [
                    10.440444946289062,
                    43.765143524274066
                  ],
                  [
                    10.36834716796875,
                    43.781505406999756
                  ],
                  [
                    10.40130615234375,
                    43.716031017652014
                  ]
                ]
              ]]
            }
          }
        EOF;
        return $string;
    }
}
