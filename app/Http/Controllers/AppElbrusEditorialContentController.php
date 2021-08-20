<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\EcPoi;
use App\Models\EcTrack;
use Illuminate\Http\JsonResponse;

class AppElbrusEditorialContentController extends Controller {
    /**
     * Api to get the ec poi geojson with the elbrus mapping
     *
     * @param int $app_id
     * @param int $poi_id
     *
     * @return JsonResponse
     */
    public function getPoiGeojson(int $app_id, int $poi_id): JsonResponse {
        $app = App::find($app_id);
        $poi = EcPoi::find($poi_id);
        if (is_null($app) || is_null($poi))
            return response()->json(['code' => 404, 'error' => 'Not found'], 404);

        return response()->json($poi->getElbrusGeojson());
    }

    /**
     * Api to get the ec track geojson with the elbrus mapping
     *
     * @param int $app_id
     * @param int $track_id
     *
     * @return JsonResponse
     */
    public function getTrackGeojson(int $app_id, int $track_id): JsonResponse {
        $app = App::find($app_id);
        $track = EcTrack::find($track_id);
        if (is_null($app) || is_null($track))
            return response()->json(['code' => 404, 'error' => 'Not found'], 404);

        return response()->json($track->getElbrusGeojson());
    }

    /**
     * Api to get the ec track json with the elbrus mapping
     *
     * @param int $app_id
     * @param int $track_id
     *
     * @return JsonResponse
     */
    public function getTrackJson(int $app_id, int $track_id): JsonResponse {
        $app = App::find($app_id);
        $track = EcTrack::find($track_id);
        if (is_null($app) || is_null($track))
            return response()->json(['code' => 404, 'error' => 'Not found'], 404);

        $geojson = $track->getElbrusGeojson();

        return response()->json($geojson['properties']);
    }
}
