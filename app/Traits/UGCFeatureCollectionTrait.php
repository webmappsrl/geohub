<?php

namespace App\Traits;

trait UGCFeatureCollectionTrait
{
    public function getUGCFeatureCollection($features, $version = 'v1')
    {
        $featureCollection = [
            'type' => 'FeatureCollection',
            'features' => [],
        ];

        if ($features) {
            foreach ($features as $feature) {
                $featureCollection['features'][] = $feature->getGeojson($version);
            }
        }

        return response()->json($featureCollection);
    }
}
