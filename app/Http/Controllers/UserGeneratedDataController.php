<?php

namespace App\Http\Controllers;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class UserGeneratedDataController extends Controller
{

    /**
     * Perform a store of a new user generated data
     *
     * @param Request $request the request
     *
     * @return JsonResponse|void
     */
    public function store(Request $request): JsonResponse
    {
        $json = json_decode($request->getContent(), true);

        if (isset($json['type']) && $json['type'] === 'FeatureCollection' && isset($json['features']) && is_array($json['features'])) {
            $createdCount = 0;
            $user = auth('api')->user();
            foreach ($json['features'] as $feature) {
                $this->_storeUgc($feature, $user);
                $createdCount++;
            }
            $message = $createdCount . ' new user generated data created';
            Log::info($message);

            return response()->json(['message' => $message, 'code' => 201], 201);
        } else return response()->json(['message' => 'The request must contain a FeatureCollection'], 422);
    }

    /**
     * Store a new User Generated Content and handle the media store and association
     *
     * @param array $feature the base feature to use to create the UGC
     * @param User|null $user the user creator of the data
     */
    private function _storeUgc(array $feature, User $user = null)
    {
        $ugcType = null;
        $userGeneratedData = null;
        if (!isset($feature['geometry']['type']) || $feature['geometry']['type'] === 'Point') {
            $userGeneratedData = new UgcPoi();
            $ugcType = 'ugc_poi';
        } else if (isset($feature['geometry']['type']) && $feature['geometry']['type'] === 'LineString') {
            $userGeneratedData = new UgcTrack();
            $ugcType = 'ugc_track';
        }

        if (!is_null($userGeneratedData)) {
            if (isset($feature['geometry']))
                $userGeneratedData->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('" . json_encode($feature['geometry']) . "'))");

            if (isset($feature['properties']['app']['id'])) {
                $userGeneratedData->app_id = $feature['properties']['app']['id'];
                unset($feature['properties']['app']);
            }

            if (isset($feature['properties']['form_data'])) {
                $userGeneratedData->name = $feature['properties']['form_data']['name'] ?? ($feature['properties']['form_data']['title'] ?? '');
                if (isset($feature['properties']['form_data']['name']))
                    unset($feature['properties']['form_data']['name']);
                elseif (isset($feature['properties']['form_data']['title']))
                    unset($feature['properties']['form_data']['title']);

                if (isset($feature['properties']['timestamp']))
                    $feature['properties']['form_data']['timestamp'] = $feature['properties']['timestamp'];

                $userGeneratedData->raw_data = json_encode($feature['properties']['form_data']);
            }

            if ($user)
                $userGeneratedData->user()->associate($user);


            if (isset($feature['properties']['form_data']['gallery']) &&
                !empty($feature['properties']['form_data']['gallery'])) {
                $gallery = explode('_', $feature['properties']['form_data']['gallery']);
                if (count($gallery) > 0) {
                    $geometry = null;
                    if (isset($feature['geometry']['type']) && $feature['geometry']['type'] === 'Point')
                        $geometry = json_encode($feature['geometry']);
                    foreach ($gallery as $rawImage) {
                        $mediaId = $this->_storeUgcMedia($rawImage, $userGeneratedData->app_id, $user, $geometry);
                        $userGeneratedData->ugc_media()->attach($mediaId);
                    }
                }
            }
            $userGeneratedData->save();

            $hoquService = app(HoquServiceProvider::class);
            $hoquService->store('update_ugc_taxonomy_where', ['id' => $userGeneratedData->id, 'type' => $ugcType]);

        }
    }

    /**
     * Store a new UGC Media
     *
     * @param string $base64 the media
     * @param string|null $appId
     * @param User|null $user the creator of the media
     * @param string|null $geometry
     *
     * @return mixed the stored media id
     */
    private function _storeUgcMedia(string $base64, string $appId = null, User $user = null, string $geometry = null)
    {
        $baseImageName = 'media/images/ugc/image_';
        $maxId = DB::table('ugc_media')->max('id');
        if (is_null($maxId)) $maxId = 0;
        $maxId++;
        preg_match("/data:image\/(.*?);/", $base64, $imageExtension);
        $image = preg_replace('/data:image\/(.*?);base64,/', '', $base64); // remove the type part
        $image = str_replace(' ', '+', $image);
        while (Storage::disk('public')->exists(
            $baseImageName . $maxId . '.' . $imageExtension[1]
        )) {
            $maxId++;
        }

        $imageName = $baseImageName . $maxId . '.' . $imageExtension[1];
        Storage::disk('public')->put(
            $imageName,
            base64_decode($image)
        );

        $newMedia = new UgcMedia();
        $newMedia->user()->associate($user);
        $newMedia->app_id = $appId;
        $newMedia->relative_url = $imageName;
        if (!is_null($geometry))
            $newMedia->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('" . $geometry . "'))");

        $newMedia->save();

        $hoquService = app(HoquServiceProvider::class);
        $hoquService->store('update_ugc_taxonomy_where', ['id' => $newMedia->id, 'type' => 'ugc_media']);

        return $newMedia->id;
    }
}
