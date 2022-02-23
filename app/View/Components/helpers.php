<?php

if (!function_exists('icon_mapping')) {
    function icon_mapping($slug){
        $mapping = $array = [
            'hiking' => 'hiking-15',
            'mtb' => 'cyc_mtb',
            'cycling' => 'cyc_bici'
        ];
        return $mapping[$slug];
    }
}

if (!function_exists('get_feature_image_thumbnail')) {
    function get_feature_image_thumbnail($feature,$size = '1440x500'){
        if (!$feature->featureImage)
            return $featured_image = asset('images/32.jpg');
            
        if (!$feature->featureImage->thumbnail($size))
            return $featured_image = asset('images/32.jpg');

        
        return $featured_image = $feature->featureImage->thumbnail($size);
    }
}

if (!function_exists('getIconSVGhtml')) {
    function getIconSVGhtml($identifier) {
        $output = '';
        if ($identifier == 'hiking') {
            $output = file_get_contents(base_path().'/resources/SVG/hiking-15.svg');
            $output = str_replace('<svg','<svg class="icon-2lg bg-light-grey rounded-full p-1 mr-2"',$output); 
        }
        if ($identifier == 'cycling') {
            $output = file_get_contents(base_path().'/resources/SVG/cyc_bici.svg');
            $output = str_replace('<svg','<svg class="icon-2lg bg-light-grey rounded-full p-1 mr-2"',$output); 
        }
        return $output;
    }
}

if (!function_exists('computeDistance')) {
        /**
     * Computes the distance between two coordinates.
     *
     * Implementation based on reverse engineering of
     * <code>google.maps.geometry.spherical.computeDistanceBetween()</code>.
     *
     * @param float $lat1 Latitude from the first point.
     * @param float $lng1 Longitude from the first point.
     * @param float $lat2 Latitude from the second point.
     * @param float $lng2 Longitude from the second point.
     * @param float $radius (optional) Radius in meters.
     *
     * @return float Distance in meters.
     */
    function computeDistance($lat1, $lng1, $lat2, $lng2, $radius = 6378137)
    {
        static $x = M_PI / 180;
        $lat1 *= $x; $lng1 *= $x;
        $lat2 *= $x; $lng2 *= $x;
        $distance = 2 * asin(sqrt(pow(sin(($lat1 - $lat2) / 2), 2) + cos($lat1) * cos($lat2) * pow(sin(($lng1 - $lng2) / 2), 2)));

        return $distance * $radius;
    }
}

if (!function_exists('convertToHoursMins')){
    function convertToHoursMins($time, $format = '%02d:%02d') {
        if ($time < 1) {
            return;
        }
        $hours = floor($time / 60);
        $minutes = ($time % 60);
        if ($hours == 0 ) {
            return sprintf('%02dmin', $minutes);
        }
        return sprintf($format, $hours, $minutes);
    }
}