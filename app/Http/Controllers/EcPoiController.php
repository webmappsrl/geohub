<?php

namespace App\Http\Controllers;

use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcPoiController extends Controller
{
    public static function getNeighbourEcMedia(int $idTrack): JsonResponse
    {
        $poi = EcPoi::find($idTrack);
        if (is_null($poi)) {
            return response()->json(['error' => 'Poi not found'], 404);
        } else {
            return response()->json($poi->getNeighbourEcMedia());
        }
    }

    public static function getAssociatedEcMedia(int $idTrack): JsonResponse
    {
        $poi = EcPoi::find($idTrack);
        if (is_null($poi)) {
            return response()->json(['error' => 'Poi not found'], 404);
        }
        $result = [
            'type' => 'FeatureCollection',
            'features' => []
        ];
        foreach ($poi->ecMedia as $media) {
            $result['features'][] = $media->getGeojson();
        }

        return response()->json($result);
    }

    public static function getFeatureImage(int $idPoi)
    {
        return response()->json(EcPoi::find($idPoi)->featureImage()->get());
    }

    /**
     * Returns an array of ID and Updated_at based on the Author emails provided
     *
     * @param $email string
     *
     *
     * @return JsonResponse with the current
     */
    public function exportPoisByAuthorEmail($email = ''): JsonResponse
    {
        if (empty($email)) {
            $ids = DB::select('select id, updated_at from ec_pois where user_id != 20548 and user_id != 17482');
            $ids = collect($ids)->pluck('updated_at', 'id');
            return response()->json($ids);
        }

        if ($email) {
            $list = [];
            $emails = explode(',', $email);
            foreach ($emails as $email) {
                $user = User::where('email', '=', $email)->first();
                $ids = EcPoi::where('user_id', $user->id)->pluck('updated_at', 'id')->toArray();
                $list = $list + $ids;
            }
            return response()->json($list);
        }
    }

    /**
     * Returns the EcPoi ID associated to an external feature
     *
     * @param string $endpoint_slug
     * @param integer $source_id
     * @return JsonResponse
     */
    public function getEcPoiFromSourceID($endpoint_slug, $source_id)
    {
        $osf_id = collect(DB::select("SELECT id FROM out_source_features where endpoint_slug='$endpoint_slug' and source_id='$source_id'"))->pluck('id')->toArray();

        $ecPoi_id = collect(DB::select("select id from ec_pois where out_source_feature_id='$osf_id[0]'"))->pluck('id')->toArray();

        return $ecPoi_id[0];
    }

    /**
     * Returns the EcPoi Geojson associated to an external feature
     *
     * @param string $endpoint_slug
     * @param integer $source_id
     * @return JsonResponse
     */
    public function getPoiGeojsonFromSourceID($endpoint_slug, $source_id)
    {
        $poi_id = $this->getEcPoiFromSourceID($endpoint_slug, $source_id);
        $poi = EcPoi::find($poi_id);
        $headers = [];

        if (is_null($poi)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($poi->getGeojson(), 200, $headers);
    }
}
