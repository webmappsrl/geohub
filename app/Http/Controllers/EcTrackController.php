<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateEcTrackAwsJob;
use App\Models\App;
use App\Models\EcTrack;
use App\Models\OutSourceFeature;
use App\Models\User;
use App\Providers\EcTrackServiceProvider;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class EcTrackController extends Controller
{
    /**
     * Return EcTrack JSON.
     *
     * @param Request $request
     * @param int     $id
     * @param array   $headers
     *
     * @return JsonResponse
     */
    public function getGeojson(Request $request, int $id, array $headers = []): JsonResponse
    {
        try {
            $version = $request->header('Api-Version', 'v1');
        } catch (Exception $e) {
            $version = 'v1';
        }
        try {
            $app = $request->header('App-Id', '3');
        } catch (Exception $e) {
            $app = null;
        }
        $url = $id . ".json";
        if (Storage::disk('wmfetracks')->exists($url)) {
            $json = Storage::disk('wmfetracks')->get($url);
            return response()->json(json_decode($json));
        }

        $track = EcTrack::find($id);

        if (is_null($track)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }
        UpdateEcTrackAwsJob::dispatch($track);
        return response()->json($track->getGeojson(), 200, $headers);
    }

    /**
     * Get a feature collection with the neighbour media
     *
     * @param int $idTrack
     *
     * @return JsonResponse
     */
    public static function getNeighbourEcMedia(int $idTrack): JsonResponse
    {
        $track = EcTrack::find($idTrack);
        if (is_null($track)) {
            return response()->json(['error' => 'Track not found'], 404);
        } else {
            return response()->json($track->getNeighbourEcMedia());
        }
    }

    /**
     * Get a feature collection with the neighbour pois
     *
     * @param int $idTrack
     *
     * @return JsonResponse
     */
    public static function getNeighbourEcPoi(int $idTrack): JsonResponse
    {
        $track = EcTrack::find($idTrack);
        if (is_null($track)) {
            return response()->json(['error' => 'Track not found'], 404);
        } else {
            return response()->json($track->getNeighbourEcPoi());
        }
    }

    /**
     * Get a feature collection with the related media
     *
     * @param int $idTrack
     *
     * @return JsonResponse
     */
    public static function getAssociatedEcMedia(int $idTrack): JsonResponse
    {
        $track = EcTrack::find($idTrack);
        if (is_null($track)) {
            return response()->json(['error' => 'Track not found'], 404);
        }
        $result = [
            'type' => 'FeatureCollection',
            'features' => []
        ];
        foreach ($track->ecMedia as $media) {
            $result['features'][] = $media->getGeojson();
        }

        return response()->json($result);
    }

    /**
     * Get a feature collection with the related pois
     *
     * @param int $idTrack
     *
     * @return JsonResponse
     */
    public static function getAssociatedEcPois(int $idTrack): JsonResponse
    {
        $track = EcTrack::find($idTrack);
        if (is_null($track)) {
            return response()->json(['error' => 'Track not found'], 404);
        }

        $result = [
            'type' => 'FeatureCollection',
            'features' => []
        ];
        foreach ($track->ecPois as $poi) {
            $result['features'][] = $poi->getGeojson();
        }

        return response()->json($result);
    }

    public static function getFeatureImage(int $idTrack): JsonResponse
    {
        $track = EcTrack::find($idTrack);
        if (is_null($track)) {
            return response()->json(['error' => 'Track not found'], 404);
        } else {
            return response()->json($track->featureImage()->get());
        }
    }

    /**
     * Update the ec track with new data from Geomixer
     *
     * @param Request $request the request with data from geomixer POST
     * @param int     $id      the id of the EcTrack
     */
    public function updateComputedData(Request $request, int $id): JsonResponse
    {
        $ecTrack = EcTrack::find($id);
        if (is_null($ecTrack)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        if (!empty($request->where_ids)) {
            $ecTrack->taxonomyWheres()->sync($request->where_ids);
        }

        // NO
        // if (!empty($request->duration)) {
        //     foreach ($request->duration as $activityIdentifier => $values) {
        //         $tax = $ecTrack->taxonomyActivities()->where('identifier', $activityIdentifier)->pluck('id')->first();
        //         $ecTrack->taxonomyActivities()->syncWithPivotValues([$tax], ['duration_forward' => $values['forward'], 'duration_backward' => $values['backward']], false);
        //     }
        // }

        // NO
        // if (
        //     isset($request->geometry)
        //     && is_array($request->geometry)
        //     && isset($request->geometry['type'])
        //     && isset($request->geometry['coordinates'])
        // ) {
        //     $ecTrack->geometry = DB::raw("public.ST_GeomFromGeojson('" . json_encode($request->geometry) . "')");
        // }

        // NO
        // if (isset($request->slope) && is_array($request->slope)) {
        //     $ecTrack->slope = json_encode($request->slope);
        // }

        if (isset($request->mbtiles) && is_array($request->mbtiles)) {
            $ecTrack->mbtiles = json_encode($request->mbtiles);
        }

        if (isset($request->elevation_chart_image) && is_string($request->elevation_chart_image)) {
            $ecTrack->elevation_chart_image = $request->elevation_chart_image;
        }

        // NO
        // if (!$ecTrack->skip_geomixer_tech) {
        //     $fields = [
        //         'distance_comp',
        //         'distance',
        //         'ele_min',
        //         'ele_max',
        //         'ele_from',
        //         'ele_to',
        //         'ascent',
        //         'descent',
        //         'duration_forward',
        //         'duration_backward',
        //     ];

        //     foreach ($fields as $field) {
        //         if (isset($request->$field)) {
        //             $ecTrack->$field = $request->$field;
        //         } else {
        //             $ecTrack->$field = null;
        //         }
        //     }
        // }


        // Related POI Order
        if (isset($request->related_pois_order)) {
            if (is_array($request->related_pois_order) && count($request->related_pois_order)) {
                $order = 1;
                foreach ($request->related_pois_order as $poi_id) {
                    $ecTrack->ecPois()->updateExistingPivot($poi_id, ['order' => $order]);
                    $order++;
                }
            }
        }

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
    public function search(Request $request): JsonResponse
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'bbox' => 'required',
            'app_id' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => []
        ];

        $bboxParam = $data['bbox'];
        $sku = $data['app_id'];

        $app = App::where('sku', '=', $sku)->first();

        if (!isset($app->id)) {
            return response()->json(['error' => 'Unknown reference app'], 400);
        }

        if (isset($bboxParam)) {
            try {
                $bbox = explode(',', $bboxParam);
                $bbox = array_map('floatval', $bbox);
            } catch (Exception $e) {
                Log::warning($e->getMessage());
            }

            if (isset($bbox) && is_array($bbox)) {
                $trackRef = $data['reference_id'] ?? null;
                if (isset($trackRef) && strval(intval($trackRef)) === $trackRef) {
                    $trackRef = intval($trackRef);
                } else {
                    $trackRef = null;
                }

                $featureCollection = EcTrackServiceProvider::getSearchClustersInsideBBox($app, $bbox, $trackRef, null, 'en');
            }
        }

        return response()->json($featureCollection);
    }

    /**
     * Get the closest ec track to the given location
     *
     * @param Request $request
     * @param string  $lon
     * @param string  $lat
     *
     * @return JsonResponse
     */
    public function nearestToLocation(Request $request, string $lon, string $lat): JsonResponse
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'app_id' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $sku = $data['app_id'];

        $app = App::where('sku', '=', $sku)->first();

        if (!isset($app->id)) {
            return response()->json(['error' => 'Unknown reference app'], 400);
        }

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => []
        ];
        try {

            if ($lon === strval(floatval($lon)) && $lat === strval(floatval($lat))) {
                $lon = floatval($lon);
                $lat = floatval($lat);
                $featureCollection = EcTrackServiceProvider::getNearestToLonLat($app, $lon, $lat);
            }
        } catch (Exception $e) {
        }

        return response()->json($featureCollection);
    }

    /**
     * Get the most viewed ec tracks
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function mostViewed(Request $request): JsonResponse
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'app_id' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $sku = $data['app_id'];

        $app = App::where('sku', '=', $sku)->first();

        if (!isset($app->id)) {
            return response()->json(['error' => 'Unknown reference app'], 400);
        }

        $featureCollection = EcTrackServiceProvider::getMostViewed($app);

        return response()->json($featureCollection);
    }

    /**
     * Get multiple ec tracks in a single geojson
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function multiple(Request $request): JsonResponse
    {
        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => []
        ];

        try {
            $ids = $request->get('ids');
            $ids = explode(',', $ids ?? null);
        } catch (Exception $e) {
        }

        if (isset($ids) && is_array($ids)) {
            $ids = array_slice($ids, 0, 10);
            $ids = array_values(array_unique($ids));
            foreach ($ids as $id) {
                if ($id === strval(intval($id))) {
                    $track = EcTrack::find($id);
                    if (isset($track)) {
                        $featureCollection["features"][] = $track->getGeojson();
                    }
                }
            }
        }

        return response()->json($featureCollection);
    }

    /**
     * Toggle the favorite on the given ec track
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse with the current
     */
    public function addFavorite(Request $request, int $id): JsonResponse
    {
        $track = EcTrack::find($id);
        if (!isset($track)) {
            return response()->json(["error" => "Unknown ec track with id $id"], 404);
        }

        $userId = auth('api')->id();
        if (!$track->isFavorited($userId)) {
            $track->toggleFavorite($userId);
        }

        return response()->json(['favorite' => $track->isFavorited($userId)]);
    }

    /**
     * Toggle the favorite on the given ec track
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse with the current
     */
    public function removeFavorite(Request $request, int $id): JsonResponse
    {
        $track = EcTrack::find($id);
        if (!isset($track)) {
            return response()->json(["error" => "Unknown ec track with id $id"], 404);
        }

        $userId = auth('api')->id();
        if ($track->isFavorited($userId)) {
            $track->toggleFavorite($userId);
        }

        return response()->json(['favorite' => $track->isFavorited($userId)]);
    }

    /**
     * Toggle the favorite on the given ec track
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse with the current
     */
    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        $track = EcTrack::find($id);
        if (!isset($track)) {
            return response()->json(["error" => "Unknown ec track with id $id"], 404);
        }

        $userId = auth('api')->id();
        $track->toggleFavorite($userId);

        return response()->json(['favorite' => $track->isFavorited($userId)]);
    }

    /**
     * Toggle the favorite on the given ec track
     *
     * @param Request $request
     *
     * @return JsonResponse with the current
     */
    public function listFavorites(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $ids = $user->favorite(EcTrack::class)->pluck('id');

        return response()->json(['favorites' => $ids]);
    }


    /**
     * Returns an array of ID and Updated_at based on the Author emails provided
     *
     * @param $email string
     *
     *
     * @return JsonResponse with the current
     */
    public function exportTracksByAuthorEmail($email = ''): JsonResponse
    {
        if (empty($email)) {
            $ids = DB::select('select id, updated_at from ec_tracks where user_id != 20548 and user_id != 17482');
            $ids = collect($ids)->pluck('updated_at', 'id');
            return response()->json($ids);
        }

        if ($email) {
            $list = [];
            $emails = explode(',', $email);
            foreach ($emails as $email) {
                $user = User::where('email', '=', $email)->first();
                $ids = EcTrack::where('user_id', $user->id)->pluck('updated_at', 'id')->toArray();
                $list = $list + $ids;
            }
            return response()->json($list);
        }
    }

    /**
     * Returns the EcTrack ID associated to an external feature
     *
     * @param string $endpoint_slug
     * @param integer $source_id
     * @return JsonResponse
     */
    public function getEcTrackFromSourceID($endpoint_slug, $source_id)
    {
        $osf_id = collect(DB::select("SELECT id FROM out_source_features where endpoint_slug='$endpoint_slug' and source_id='$source_id'"))->pluck('id')->toArray();

        if (empty($osf_id)) {
            return null;
        }

        $ectrack_id = collect(DB::select("select id from ec_tracks where out_source_feature_id='$osf_id[0]'"))->pluck('id')->toArray();

        return $ectrack_id[0];
        $headers = [];

        if (is_null($track)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($track->getGeojson(), 200, $headers);
    }

    /**
     * Returns the EcTrack GeoJson associated to an external feature
     *
     * @param string $endpoint_slug
     * @param integer $source_id
     * @return JsonResponse
     */
    public function getTrackGeojsonFromSourceID($endpoint_slug, $source_id)
    {
        $track_id = $this->getEcTrackFromSourceID($endpoint_slug, $source_id);
        $track = EcTrack::find($track_id);
        $headers = [];

        if (is_null($track)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return response()->json($track->getGeojson(), 200, $headers);
    }

    /**
     * Returns the EcTrack Webapp URL associated to an external feature
     *
     * @param string $endpoint_slug
     * @param integer $source_id
     * @return JsonResponse
     */
    public function getEcTrackWebappURLFromSourceID($endpoint_slug, $source_id)
    {
        $track_id = $this->getEcTrackFromSourceID($endpoint_slug, $source_id);
        $track = EcTrack::find($track_id);
        $app_id = $track->user->apps[0]->id;


        $headers = [];

        if (is_null($track) || empty($app_id)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return redirect('https://' . $app_id . '.app.webmapp.it/#/map?track=' . $track_id);
    }

    /**
     * Get the feature collection for the given track pdf
     *
     * @param int $idTrack
     *
     * @return JsonResponse
     */
    public static function getFeatureCollectionForTrackPdf(int $idTrack): JsonResponse
    {
        $track = EcTrack::find($idTrack);
        if (is_null($track)) {
            return response()->json(['error' => 'Track not found'], 404);
        }
        $trackGeometry = Db::select("select ST_AsGeoJSON(geometry) as geometry from ec_tracks where id = $idTrack");
        $trackGeometry = json_decode($trackGeometry[0]->geometry);

        //feature must have properties field as follow: {"type":"Feature","properties":{"id":1, "type":"track/poi", "strokeColor": "", "fillColor": ""},"geometry":{"type":"LineString","coordinates":[[11.123,45.123],[11.123,45.123]]}}

        $features = [];

        $trackFeature = [
            "type" => "Feature",
            "properties" => [
                "id" => $track->id,
                "type_sisteco" => "Track",
                "strokeColor" => "",
                "fillColor" => ""
            ],
            "geometry" => $trackGeometry
        ];

        $features[] = $trackFeature;



        //if the track has related pois we add them to the feature collection, else we return the track feature only
        if (count($track->ecPois) > 0) {
            foreach ($track->ecPois as $poi) {
                $poiGeometry = Db::select("select ST_AsGeoJSON(geometry) as geometry from ec_pois where id = $poi->id");
                $poiGeometry = json_decode($poiGeometry[0]->geometry);
                $poiFeature = [
                    "type" => "Feature",
                    "properties" => [
                        "id" => $poi->id,
                        "type_sisteco" => "Poi",
                        "pointRadius" => "",
                        "pointFillColor" => "",
                        "pointStrokeColor" => "",
                    ],
                    "geometry" => $poiGeometry
                ];
                $features[] = $poiFeature;
            }
        }


        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features
        ];

        return response()->json($featureCollection);
    }
}
