<?php

namespace App\Traits;

use App\Enums\AppTiles;
use App\Models\App;
use App\Models\EcMedia;
use App\Models\OverlayLayer;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait UGCFeatureCollectionTrait
{

    public function getUGCFeatureCollection($features, $version = 'v1')
    {
        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => []
        ];

        if ($features) {
            foreach ($features as $feature) {
                $featureCollection["features"][] = $feature->getGeojson($version);
            }
        }

        return response()->json($featureCollection);
    }
}
