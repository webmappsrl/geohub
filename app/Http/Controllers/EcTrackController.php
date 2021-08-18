<?php

namespace App\Http\Controllers;

use App\Models\EcTrack;
use App\Providers\EcTrackServiceProvider;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcTrackController extends Controller {
    public static function getNeighbourEcMedia(int $idTrack): JsonResponse {
        $track = EcTrack::find($idTrack);
        if (is_null($track))
            return response()->json(['error' => 'Track not found'], 404);
        else
            return response()->json($track->getNeighbourEcMedia());
    }

    public static function getNeighbourEcPoi(int $idTrack): JsonResponse {
        $track = EcTrack::find($idTrack);
        if (is_null($track))
            return response()->json(['error' => 'Track not found'], 404);
        else
            return response()->json($track->getNeighbourEcPoi());
    }

    public static function getAssociatedEcMedia(int $idTrack): JsonResponse {
        $track = EcTrack::find($idTrack);
        if (is_null($track))
            return response()->json(['error' => 'Track not found'], 404);
        else
            return response()->json($track->ecMedia()->get());
    }

    public static function getAssociatedEcPoi(int $idTrack): JsonResponse {
        $track = EcTrack::find($idTrack);
        if (is_null($track))
            return response()->json(['error' => 'Track not found'], 404);
        else
            return response()->json($track->ecPois()->get());
    }

    public static function getFeatureImage(int $idTrack): JsonResponse {
        $track = EcTrack::find($idTrack);
        if (is_null($track))
            return response()->json(['error' => 'Track not found'], 404);
        else
            return response()->json($track->featureImage()->get());
    }

    /**
     * Update the ec track with new data from Geomixer
     *
     * @param Request $request the request with data from geomixer POST
     * @param int     $id      the id of the EcTrack
     */
    public function updateComputedData(Request $request, int $id): JsonResponse {
        $ecTrack = EcTrack::find($id);
        if (is_null($ecTrack)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        if (!empty($request->where_ids)) {
            $ecTrack->taxonomyWheres()->sync($request->where_ids);
        }

        if (!empty($request->duration)) {
            foreach ($request->duration as $activityIdentifier => $values) {
                $tax = $ecTrack->taxonomyActivities()->where('identifier', $activityIdentifier)->pluck('id')->first();
                $ecTrack->taxonomyActivities()->syncWithPivotValues([$tax], ['duration_forward' => $values['forward'], 'duration_backward' => $values['backward']], false);
            }
        }

        if (
            !is_null($request->geometry)
            && is_array($request->geometry)
            && isset($request->geometry['type'])
            && isset($request->geometry['coordinates'])
        ) {
            $ecTrack->geometry = DB::raw("public.ST_GeomFromGeojson('" . json_encode($request->geometry) . "')");
        }

        $fields = [
            'distance_comp',
            'distance',
            'ele_min',
            'ele_max',
            'ele_from',
            'ele_to',
            'ascent',
            'descent',
            'duration_forward',
            'duration_backward',
        ];

        foreach ($fields as $field) {
            if (isset($request->$field)) {
                $ecTrack->$field = $request->$field;
            } else $ecTrack->$field = null;
        }

        Log::info($ecTrack->ele_max);

        $ecTrack->skip_update = true;
        $ecTrack->save();

        return response()->json();
    }

    /**
     * Search the ec tracks using the GET parameters
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse {
        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => []
        ];

        $bboxParam = $request->get('bbox');
        if (isset($bboxParam)) {
            try {
                $bbox = explode(',', $bboxParam);
                $bbox = array_map('floatval', $bbox);
            } catch (Exception $e) {
                Log::warning();
            }

            if (isset($bbox) && is_array($bbox)) {
                $featureCollection = EcTrackServiceProvider::getSearchClustersInsideBBox($bbox);
            }
        }

        return response()->json($featureCollection);
    }
}
