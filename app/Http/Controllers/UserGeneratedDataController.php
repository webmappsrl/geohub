<?php

namespace App\Http\Controllers;

use App\Models\UserGeneratedData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserGeneratedDataController extends Controller
{
    public function store(Request $request)
    {
        $json = json_decode($request->getContent(), true);

        if (isset($json['type']) && $json['type'] === 'FeatureCollection' && isset($json['features']) && is_array($json['features'])) {
            $createdCount = 0;
            foreach ($json['features'] as $feature) {
                $userGeneratedData = new UserGeneratedData();

                if (isset($feature['geometry'])) {
                    $userGeneratedData->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('" . json_encode($feature['geometry']) . "'))");
                }

                if (isset($feature['properties']['app']['id'])) {
                    $userGeneratedData->app_id = $feature['properties']['app']['id'];
                    unset($feature['properties']['app']);
                }

                if (isset($feature['properties']['timestamp'])) {
                    $userGeneratedData->created_at = $feature['properties']['timestamp'];
                    $userGeneratedData->updated_at = $feature['properties']['timestamp'];
                    unset($feature['properties']['timestamp']);
                }

                if (isset($feature['properties']['form_data'])) {
                    if (isset($feature['properties']['form_data']['gallery']) && !empty($feature['properties']['form_data']['gallery'])) {
                        $gallery = explode('_', $feature['properties']['form_data']['gallery']);
                        $userGeneratedData->raw_gallery = json_encode($gallery);
                        unset($feature['properties']['form_data']['gallery']);
                    }

                    $userGeneratedData->raw_data = json_encode($feature['properties']['form_data']);
                }

                $userGeneratedData->save();
                $createdCount++;
            }
            $message = $createdCount . ' new user generated data created';
            Log::info($message);
            return response()->json(['message' => $message, 'code' => 201], 201);
        } else return abort(422, 'The request must contain a FeatureCollection');

    }
}
