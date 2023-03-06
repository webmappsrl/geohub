<?php

namespace App\Http\Controllers;

use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $ids = collect($ids)->pluck('updated_at','id'); 
            return response()->json($ids);
        }
        
        if ($email) {
            $list = [];
            $emails = explode(',',$email);
            foreach ($emails as $email) {
                $user = User::where('email', '=', $email)->first();
                $ids = EcPoi::where('user_id',$user->id)->pluck('updated_at','id')->toArray();
                $list = $list + $ids;
            }
            return response()->json($list);
        }
    }
}
