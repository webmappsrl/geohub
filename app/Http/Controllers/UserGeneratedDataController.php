<?php

namespace App\Http\Controllers;

use App\Models\TaxonomyWhere;
use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use App\Models\User;
use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserGeneratedDataController extends Controller
{
    use GeometryFeatureTrait;

    /**
     * Perform a store of a new user generated data
     *
     * @param  Request  $request  the request
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
            $message = $createdCount.' new user generated data created';
            Log::info($message);

            return response()->json(['message' => $message, 'code' => 201], 201);
        } else {
            return response()->json(['message' => 'The request must contain a FeatureCollection'], 422);
        }
    }

    /**
     * Store a new User Generated Content and handle the media store and association
     *
     * @param  array  $feature  the base feature to use to create the UGC
     * @param  User|null  $user  the user creator of the data
     */
    private function _storeUgc(array $feature, ?User $user = null): void
    {
        $ugcType = null;
        $userGeneratedData = null;
        if (! isset($feature['geometry']['type']) || $feature['geometry']['type'] === 'Point') {
            $userGeneratedData = new UgcPoi;
            $ugcType = 'poi';
        } elseif (isset($feature['geometry']['type']) && $feature['geometry']['type'] === 'LineString') {
            $userGeneratedData = new UgcTrack;
            $ugcType = 'track';
        }

        if (! is_null($userGeneratedData)) {
            if (isset($feature['geometry'])) {
                $userGeneratedData->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('".json_encode($feature['geometry'])."'))");
            }

            if (isset($feature['properties']['app']['id'])) {
                $userGeneratedData->app_id = $feature['properties']['app']['id'];
                unset($feature['properties']['app']);
            }

            if (isset($feature['properties']['form_data'])) {
                $userGeneratedData->name = $feature['properties']['form_data']['name'] ?? ($feature['properties']['form_data']['title'] ?? '');
                if (isset($feature['properties']['form_data']['name'])) {
                    unset($feature['properties']['form_data']['name']);
                } elseif (isset($feature['properties']['form_data']['title'])) {
                    unset($feature['properties']['form_data']['title']);
                }

                if (isset($feature['properties']['timestamp'])) {
                    $feature['properties']['form_data']['timestamp'] = $feature['properties']['timestamp'];
                }

                $data = $feature['properties']['form_data'];
                if (isset($data['gallery'])) {
                    unset($data['gallery']);
                }

                $userGeneratedData->raw_data = json_encode($data);
            }

            if ($user) {
                $userGeneratedData->user()->associate($user);
            }

            // This is needed to make sure the media attachment work
            $userGeneratedData->save();

            if (
                isset($feature['properties']['form_data']['gallery']) &&
                ! empty($feature['properties']['form_data']['gallery'])
            ) {
                $gallery = explode('_', $feature['properties']['form_data']['gallery']);
                if (count($gallery) > 0) {
                    $geometry = null;
                    if (isset($feature['geometry']['type']) && $feature['geometry']['type'] === 'Point') {
                        $geometry = json_encode($feature['geometry']);
                    }
                    foreach ($gallery as $rawImage) {
                        $mediaId = $this->_storeUgcMedia($rawImage, $userGeneratedData->app_id, $user, $geometry);
                        $userGeneratedData->ugc_media()->attach($mediaId);
                    }
                }
            }
            $userGeneratedData->save();

            $hoquService = app(HoquServiceProvider::class);
            $hoquService->store('update_ugc_taxonomy_wheres', ['id' => $userGeneratedData->id, 'type' => $ugcType]);
        }
    }

    /**
     * Store a new UGC Media
     *
     * @param  string  $base64  the media
     * @param  User|null  $user  the creator of the media
     * @return int the stored media id
     */
    private function _storeUgcMedia(string $base64, ?string $appId = null, ?User $user = null, ?string $geometry = null): int
    {
        $baseImageName = 'media/images/ugc/image_';
        $maxId = DB::table('ugc_media')->max('id');
        if (is_null($maxId)) {
            $maxId = 0;
        }
        $maxId++;
        preg_match("/data:image\/(.*?);/", $base64, $imageExtension);
        $image = preg_replace('/data:image\/(.*?);base64,/', '', $base64); // remove the type part
        $image = str_replace(' ', '+', $image);
        while (Storage::disk('public')->exists(
            $baseImageName.$maxId.'.'.$imageExtension[1]
        )) {
            $maxId++;
        }

        $imageName = $baseImageName.$maxId.'.'.$imageExtension[1];
        Storage::disk('public')->put(
            $imageName,
            base64_decode($image)
        );

        $newMedia = new UgcMedia;
        $newMedia->user()->associate($user);
        $newMedia->app_id = $appId;
        $newMedia->relative_url = $imageName;
        if (! is_null($geometry)) {
            $newMedia->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('".$geometry."'))");
        }

        $newMedia->save();

        $hoquService = app(HoquServiceProvider::class);
        $hoquService->store('update_ugc_taxonomy_wheres', ['id' => $newMedia->id, 'type' => 'media']);

        return $newMedia->id;
    }

    /**
     * Calculate the model class name of a ugc from its type
     *
     * @param  string  $type  the ugc type
     * @return string the model class name
     *
     * @throws Exception
     */
    private function _getUgcModelFromType(string $type): string
    {
        switch ($type) {
            case 'poi':
                $model = "\App\Models\UgcPoi";
                break;
            case 'track':
                $model = "\App\Models\UgcTrack";
                break;
            case 'media':
                $model = "\App\Models\UgcMedia";
                break;
            default:
                throw new Exception("Invalid type ' . $type . '. Available types: poi, track, media");
        }

        return $model;
    }

    /**
     * Get Ugc by ID as geoJson
     *
     * @param  int  $id  the Ugc id
     * @return JsonResponse return the Ugc geojson
     */
    public function getUgcGeojson(int $id): JsonResponse
    {
        $apiUrl = explode('/', request()->path());
        try {
            $model = $this->_getUgcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ugc = $model::find($id);
        $ugc = ! is_null($ugc) ? $ugc->getGeojson() : null;
        $ugc['properties']['user_email'] = User::find($ugc['properties']['user_id'])->email;
        if (is_null($ugc)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        return response()->json($ugc);
    }

    public function getUgcGeojsonOsm2cai(int $id)
    {
        $apiUrl = explode('/', request()->path());
        try {
            $model = $this->_getUgcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }
        $ugc = $model::find($id);
        if (is_null($ugc)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        $taxonomyWheres = $ugc->taxonomy_wheres;
        $taxonomyWheresNames = [];
        if (count($taxonomyWheres) > 0) {
            foreach ($taxonomyWheres as $taxonomyWhere) {
                $taxonomyWheresNames[] = $taxonomyWhere->name;
            }
            $taxonomyWheresNames = implode(',', $taxonomyWheresNames);
        } else {
            $taxonomyWheresNames = null;
        }

        $ugcGeojson = ! is_null($ugc) ? $ugc->getGeojson() : null;
        $email = $ugcGeojson['properties']['user_id'] ? User::find($ugcGeojson['properties']['user_id'])->email : '';
        $ugcGeojson['properties']['user_email'] = $email;
        if (! is_null($taxonomyWheresNames)) {
            $ugcGeojson['properties']['taxonomy_wheres'] = $taxonomyWheresNames;
        }
        if ($ugc instanceof UgcMedia) {
            $ugcGeojson['properties']['relative_url'] = $ugc->relative_url;
            $ugcPois = $ugc->ugc_pois;
            $ugcTracks = $ugc->ugc_tracks;
            if (count($ugcPois) > 0) {
                $ugcGeojson['properties']['ugc_pois'] = [];
                foreach ($ugcPois as $ugcPoi) {
                    $ugcGeojson['properties']['ugc_pois'][] = $ugcPoi->id;
                }
            }
            if (count($ugcTracks) > 0) {
                $ugcGeojson['properties']['ugc_tracks'] = [];
                foreach ($ugcTracks as $ugcTrack) {
                    $ugcGeojson['properties']['ugc_tracks'][] = $ugcTrack->id;
                }
            }
        } else {
            $ugcMedia = $ugc->ugc_media;
            if (count($ugcMedia) > 0) {
                $ugcGeojson['properties']['ugc_media'] = [];
                foreach ($ugcMedia as $media) {
                    $ugcGeojson['properties']['ugc_media'][] = $media->id;
                }
            }
        }

        return response()->json($ugcGeojson);
    }

    /**
     *  Associate UcgFeature to TaxonomyWhere
     *
     * @param  Request  $request  the request
     */
    public function associateTaxonomyWhereWithUgcFeature(Request $request): JsonResponse
    {
        $apiUrl = explode('/', request()->path());
        try {
            $model = $this->_getUgcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $params = $request->all();

        if (! isset($params['id']) || empty($params['id'])) {
            return response()->json([
                'code' => 400,
                'message' => "The parameter 'id' is missing but required. The operation can not be completed",
            ], 400);
        }

        $id = $params['id'];

        if (! isset($params['where_ids']) || empty($params['where_ids']) || ! is_array($params['where_ids'])) {
            $whereIds = [];
        } else {
            $whereIds = $params['where_ids'];
        }

        $ugc = $model::find($id);
        $validIds = [];
        foreach ($whereIds as $whereId) {
            $where = TaxonomyWhere::find($whereId);
            if (! is_null($where)) {
                $validIds[] = $whereId;
            }
        }

        $ugc->taxonomy_wheres()->sync($validIds);

        return response()->json([]);
    }

    /**
     * Get Ugc list by app_id as json
     *
     * @param  int  $id  the Ugc id
     */
    public function getUgcList(string $app_id)
    {
        $apiUrl = explode('/', request()->path());
        $list = [];
        try {
            $model = $this->_getUgcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ugcs = $model::where('app_id', $app_id)->get();
        if (is_null($ugcs)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        } else {
            foreach ($ugcs as $ugc) {
                $list[$ugc->id] = $ugc->updated_at;
            }
        }

        return $list;
    }
}
