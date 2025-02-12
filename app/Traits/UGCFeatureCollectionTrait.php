<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait UGCFeatureCollectionTrait
{
    public function getUGCFeatureCollection($features, $version = 'v1')
    {
        $featureCollection = [
            'type' => 'FeatureCollection',
            'features' => [],
        ];
        if ($version != 'v1') {
            foreach ($features as $feature) {
                $media = $feature->ugc_media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'name' => $media->name,
                        'description' => $media->description,
                        'webPath' => Storage::disk('public')->url($media->relative_url), // Assicurati che 'url' esista in UgcMedia
                    ];
                });
                $properties = $feature->properties;
                $properties['media'] = $media;
                $properties['photos'] = $media; // da eliminare
                $feature->setAttribute('properties', $properties);
            }
        }

        if ($features) {
            foreach ($features as $feature) {
                $featureCollection['features'][] = $feature->getGeojson($version);
            }
        }

        return response()->json($featureCollection);
    }
}
