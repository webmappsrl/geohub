<?php

namespace App\Http\Controllers;

use App\Models\EcPoi;
use App\Models\EcTrack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcPoiController extends Controller {
    public static function getNeighbourEcMedia(int $idTrack): JsonResponse {
        $poi = EcPoi::find($idTrack);
        if (is_null($poi))
            return response()->json(['error' => 'Poi not found'], 404);
        else
            return response()->json($poi->getNeighbourEcMedia());
    }

    public static function getAssociatedEcMedia(int $idTrack): JsonResponse {
        $poi = EcPoi::find($idTrack);
        if (is_null($poi))
            return response()->json(['error' => 'Poi not found'], 404);
        $result = [
            'type' => 'FeatureCollection',
            'features' => []
        ];
        foreach ($poi->ecMedia as $media) {
            $result['features'][] = $media->getGeojson();
        }

        return response()->json($result);
    }

    public static function getFeatureImage(int $idPoi) {
        return response()->json(EcPoi::find($idPoi)->featureImage()->get());
    }
}
