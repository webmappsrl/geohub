<?php

namespace App\Helpers;

class GeoJsonHelper {

    public static function isGeojson($string) {
        $gj = json_decode($string);
        return isset($gj->type);
    }

    public static function isGeojsonFeature($string) {
        $gj = json_decode($string);
        if(isset($gj->type) && $gj->type=='Feature'){
            return true;
        }
        return false;
    }

    public static function isGeojsonFeatureCollection($string) {
        $gj = json_decode($string);
        if(isset($gj->type) && $gj->type=='FeatureCollection'){
            return true;
        }
        return false;
    }

    public static function convertCollectionToFirstFeature($string){
        if(self::isGeojsonFeature($string)) {
            return $string;
        }
        else if (self::isGeojsonFeatureCollection($string)) {
            $gj = json_decode($string);
            if(isset($gj->features) && is_array($gj->features) && count($gj->features)>0) {
                return json_encode($gj->features[0]);
            }
        }
    }

    public static function isGeojsonFeaturePolygon($string) {
        $gj = json_decode($string);
        if(isset($gj->type) &&
            $gj->type == 'Feature' &&
            isset($gj->geometry) &&
            isset($gj->geometry->type) &&
            $gj->geometry->type == 'Polygon'
        ) {
            return true;
        }
        return false;
    }

    public static function isGeojsonFeatureMultiPolygon($string) {
        $gj = json_decode($string);
        if(isset($gj->type) &&
            $gj->type == 'Feature' &&
            isset($gj->geometry) &&
            isset($gj->geometry->type) &&
            $gj->geometry->type == 'MultiPolygon'
        ) {
            return true;
        }
        return false;
    }

    public static function convertPolygonToMultiPolygon($string) {
        if(self::isGeojsonFeatureMultiPolygon($string)) {
            return $string;
        }
        else if (self::isGeojsonFeaturePolygon($string)) {
            $gj = json_decode($string);
            $new_coords = [$gj->geometry->coordinates];
            $gj->geometry->type='MultiPolygon';
            $gj->geometry->coordinates=$new_coords;
            return json_encode($gj);
        }
    }
}
