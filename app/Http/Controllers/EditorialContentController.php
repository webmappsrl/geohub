<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Traits\GeometryFeatureTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

define('CONTENT_TYPE_IMAGE_MAPPING', [
    'bmp' => 'image/bmp',
    'gif' => 'image/gif',
    'ico' => 'image/vnd.microsoft.icon',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'svg' => 'image/svg+xml',
    'tif' => 'image/tiff',
    'webp' => 'image/webp'
]);

class EditorialContentController extends Controller
{

    use GeometryFeatureTrait;

    /**
     * Calculate the model class name of a ugc from its type
     *
     * @param string $type the ugc type
     *
     * @return string the model class name
     *
     * @throws Exception
     */
    private function _getEcModelFromType(string $type): string
    {
        switch ($type) {
            case 'poi':
                $model = "\App\Models\EcPoi";
                break;
            case 'track':
                $model = "\App\Models\EcTrack";
                break;
            case 'media':
                $model = "\App\Models\EcMedia";
                break;
            default:
                throw new Exception("Invalid type ' . $type . '. Available types: poi, track, media");
        }

        return $model;
    }

    /**
     * Get Ec info by ID
     *
     * @param int $id the Ec id
     *
     * @return JsonResponse return the Ec info
     */
    public function getEcJson(int $id): JsonResponse
    {
        $apiUrl = explode("/", request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ec = $model::find($id);
        if (is_null($ec)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        if ('media' !== $apiUrl[2]) {
            $downloadUrls = [
                'geojson' => route('api.ec.' . $apiUrl[2] . '.download.geojson', ['id' => $id]),
                'gpx' => route('api.ec.' . $apiUrl[2] . '.download.gpx', ['id' => $id]),
                'kml' => route('api.ec.' . $apiUrl[2] . '.download.kml', ['id' => $id]),
            ];
            $geojson = $ec->getGeojson($downloadUrls);
            if ($ec->featureImage) {
                $geojson['properties']['image'] = json_decode($ec->featureImage->getJson(), true);
            }

            if ($ec->ecMedia) {
                $gallery = [];
                $ecMedia = $ec->ecMedia;
                foreach ($ecMedia as $media) {
                    $gallery[] = json_decode($media->getJson(), true);
                }

                if (count($gallery)) {
                    $geojson['properties']['imageGallery'] = $gallery;
                }
            }
        } else {
            $geojson = $ec->getGeojson([]);
        }

        // Add Taxonomies
        $taxonomies = $this->_getTaxonomies($ec, $names = ['activity', 'theme', 'where', 'who', 'when']);
        $geojson['properties']['taxonomy'] = $taxonomies;

        if ('track' == $apiUrl[2]) {
            $durations = $this->_getDurations($ec, $names = ['hiking', 'cycling']);
            $geojson['properties']['duration'] = $durations;
        }

        return response()->json($geojson);
    }

    /**
     * Controller for API api/app/elbrus/{app_id}/geojson/ec_poi_{poi_id}.geojson
     *
     * @param int $app_id
     * @param int $poi_id
     *
     * @return JsonResponse
     */
    public function getElbrusPoiGeojson(int $app_id, int $poi_id): JsonResponse
    {
        $app = App::find($app_id);
        $poi = EcPoi::find($poi_id);
        if (is_null($app) || is_null($poi)) {
            return response()->json(['code' => 404, 'error' => 'Not found'], 404);
        }
        $geojson = $poi->getGeojson();
        // MAPPING
        $geojson['properties']['id'] = 'ec_poi_' . $poi->id;
        $geojson = $this->_mapGeojsonPropertyForElbrusApi($geojson, 'contact_phone');
        $geojson = $this->_mapGeojsonPropertyForElbrusApi($geojson, 'contact_email');

        // Add Taxonomies
        $taxonomies = $this->_getTaxonomies($poi);
        $geojson['properties']['taxonomy'] = $taxonomies;

        return response()->json($geojson, 200);
    }

    private function _getTaxonomies($obj, $names = ['activity', 'theme', 'where', 'who', 'when', 'webmapp_category'])
    {
        $taxonomies = [];
        foreach ($names as $name) {
            switch ($name) {
                case 'activity':
                    $terms = $obj->taxonomyActivities()->pluck('id')->toArray();
                    break;
                case 'theme':
                    $terms = $obj->taxonomyThemes()->pluck('id')->toArray();
                    break;
                case 'where':
                    $terms = $obj->taxonomyWheres()->pluck('id')->toArray();
                    break;
                case 'who':
                    $terms = $obj->taxonomyTargets()->pluck('id')->toArray();
                    break;
                case 'when':
                    $terms = $obj->taxonomyWhens()->pluck('id')->toArray();
                    break;
                case 'webmapp_category':
                    $terms = $obj->taxonomyPoiTypes()->pluck('id')->toArray();
                    break;
            }
            if (count($terms) > 0) {
                foreach ($terms as $term) {
                    $taxonomies[$name][] = $name . '_' . $term;
                }
            }
        }

        return $taxonomies;
    }

    private function _getDurations($obj, $names = ['hiking', 'cycling'])
    {
        $durations = [];
        $activityTerms = $obj->taxonomyActivities()->whereIn('identifier', $names)->get()->toArray();
        if (count($activityTerms) > 0) {
            foreach ($activityTerms as $term) {
                $durations[$term['identifier']] = [
                    'forward' => $term['pivot']['duration_forward'],
                    'backward' => $term['pivot']['duration_backward'],
                ];
            }
        }

        return $durations;
    }

    /**
     * Controller for API api/app/elbrus/{app_id}/geojson/ec_track_{poi_id}.geojson
     *
     * @param int $app_id
     * @param int $poi_id
     *
     * @return JsonResponse
     */
    public function getElbrusTrackGeojson(int $app_id, int $track_id): JsonResponse
    {
        $app = App::find($app_id);
        $track = EcTrack::find($track_id);
        if (is_null($app) || is_null($track)) {
            return response()->json(['code' => 404, 'error' => 'Not found'], 404);
        }


        return response()->json($this->_getElbrusTracksGeojsonComplete($app_id, $track_id), 200);
    }

    /**
     * Controller for API api/app/elbrus/{app_id}/geojson/ec_track_{poi_id}.json
     *
     * @param int $app_id
     * @param int $poi_id
     *
     * @return JsonResponse
     */
    public function getElbrusTrackJson(int $app_id, int $track_id): JsonResponse
    {
        $app = App::find($app_id);
        $track = EcTrack::find($track_id);
        if (is_null($app) || is_null($track)) {
            return response()->json(['code' => 404, 'error' => 'Not found'], 404);
        }
        $geojson = $this->_getElbrusTracksGeojsonComplete($app_id, $track_id);

        return response()->json($geojson['properties'], 200);
    }

    private function _getElbrusTracksGeojsonComplete(int $app_id, int $track_id): array
    {
        $app = App::find($app_id);
        $track = EcTrack::find($track_id);
        $geojson = $track->getGeojson();
        // MAPPING COLON
        $geojson['properties']['id'] = 'ec_track_' . $track->id;
        $fields = [
            'ele_from', 'ele_to', 'ele_max', 'ele_min', 'duration_forward', 'duration_backward'
        ];
        foreach ($fields as $field) {
            $geojson = $this->_mapGeojsonPropertyForElbrusApi($geojson, $field);
        }
        // Add featureImage
        if ($track->featureImage) {
            $geojson['properties']['image'] = json_decode($track->featureImage->getJson(), true);
        }

        if ($track->ecMedia) {
            $gallery = [];
            $ecMedia = $track->ecMedia;
            foreach ($ecMedia as $media) {
                $gallery[] = json_decode($media->getJson(), true);
            }

            if (count($gallery)) {
                $geojson['properties']['imageGallery'] = $gallery;
            }
            $geojson['properties']['gpx_url'] = route('api.ec.track.download.gpx', ['id' => $track_id]);
            $geojson['properties']['kml_url'] = route('api.ec.track.download.kml', ['id' => $track_id]);
        }

        // Add Taxonomies
        $taxonomies = $this->_getTaxonomies($track, $names = ['activity', 'theme', 'where', 'who', 'when']);
        $geojson['properties']['taxonomy'] = $taxonomies;

        $durations = $this->_getDurations($track, $names = ['hiking', 'cycling']);
        $geojson['properties']['duration'] = $durations;

        return $geojson;
    }

    /**
     * Convert $geojson['properties']['example_nocolon'] to
     * $geojson['properties']['example:colon']. If parameter $field_with_colon is left null
     * then is derived from $filed using the rule "_" -> ":"
     *
     * @param      $geojson
     * @param      $field
     * @param null $field_with_colon
     */
    private function _mapGeojsonPropertyForElbrusApi($geojson, $field, $field_with_colon = null)
    {
        if (isset($geojson['properties'][$field])) {
            if (is_null($field_with_colon)) {
                $field_with_colon = preg_replace('/_/', ':', $field);
            }
            $geojson['properties'][$field_with_colon] = $geojson['properties'][$field];
        }

        return $geojson;
    }

    /**
     * Get Ec image by ID
     *
     * @param int $id the Ec id
     *
     *
     */
    public function getEcImage(int $id)
    {
        $apiUrl = explode("/", request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ec = $model::find($id);
        if (is_null($ec))
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);


        // https://wmptest.s3.eu-central-1.amazonaws.com/EcMedia/2.jpg

        /*if (preg_match('/\.amazonaws\.com\//', $ec->url)) {
            $explode = explode('.amazonaws.com/', $ec->url);
            $url = end($explode);
            Log::info($url);
            return Storage::disk('s3')->download($url, 'name' . '.jpg');
        }*/
        $pathInfo = pathinfo(parse_url($ec->url)['path']);
        if (substr($ec->url, 0, 4) === 'http') {
            header("Content-disposition:attachment; filename=name." . $pathInfo['extension']);
            header('Content-Type:' . CONTENT_TYPE_IMAGE_MAPPING[$pathInfo['extension']]);
            readfile($ec->url);
        } else {
            //Scaricare risorsa locale
            return Storage::disk('public')->download($ec->url, 'name.' . $pathInfo['extension']);
        }
    }

    /** Update the ec media with new data from Geomixer
     *
     * @param Request $request the request with data from geomixer POST
     * @param int $id the id of the EcMedia
     */
    public function updateEcMedia(Request $request, $id)
    {
        $ecMedia = EcMedia::find($id);

        if (is_null($ecMedia))
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        $actualUrl = $ecMedia->url;
        if (is_null($request->url))
            return response()->json(['code' => 400, 'error' => "Missing mandatory parameter: URL"], 400);
        $ecMedia->url = $request->url;

        if (
            !is_null($request->geometry)
            && is_array($request->geometry)
            && isset($request->geometry['type'])
            && isset($request->geometry['coordinates'])
        ) {
            $ecMedia->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('" . json_encode($request->geometry) . "'))");
        }

        if (!empty($request->where_ids)) {
            $ecMedia->taxonomyWheres()->sync($request->where_ids);
        }

        if (!empty($request->thumbnail_urls)) {
            $ecMedia->thumbnails = $request->thumbnail_urls;
        }

        $ecMedia->save();

        try {
            $headers = get_headers($request->url);

            if (stripos($headers[0], "200 OK") >= 0)
                Storage::disk('public')->delete($actualUrl);
        } catch (Exception $e) {
            Log::warning($e->getMessage());
        }
    }

    /** Update the ec track with new data from Geomixer
     *
     * @param Request $request the request with data from geomixer POST
     * @param int $id the id of the EcTrack
     */
    public function updateEcTrack(Request $request, $id)
    {
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
            }
        }

        $ecTrack->skip_update = true;
        $ecTrack->save();
    }

    /** Update the ec media with new data from Geomixer
     *
     * @param Request $request the request with data from geomixer POST
     * @param int $id the id of the EcMedia
     */
    public function updateEcPoi(Request $request, $id)
    {
        $ecPoi = EcPoi::find($id);

        if (is_null($ecPoi)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        if (
            !is_null($request->geometry)
            && is_array($request->geometry)
            && isset($request->geometry['type'])
            && isset($request->geometry['coordinates'])
        ) {
            $ecPoi->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('" . json_encode($request->geometry) . "'))");
        }

        $fields = [
            'ele',
        ];

        foreach ($fields as $field) {
            if (isset($request->$field)) {
                $ecPoi->$field = $request->$field;
            }
        }

        if (!empty($request->where_ids)) {
            $ecPoi->taxonomyWheres()->sync($request->where_ids);
        }

        $ecPoi->skip_update = true;
        $ecPoi->save();
    }

    /**
     * Return geometry formatted by $format.
     *
     * @param Request $request the request with data from geomixer POST
     * @param int $id
     * @param string $format
     *
     * @return Response
     */
    public function downloadEcPoi(Request $request, int $id, string $format = 'geojson')
    {
        $ecPoi = EcPoi::find($id);

        $response = response()->json(['code' => 404, 'error' => "Not Found"], 404);
        if (is_null($ecPoi)) {
            return $response;
        }

        $headers = [];
        $downloadUrls = [
            'geojson' => route('api.ec.poi.download.geojson', ['id' => $id]),
            'gpx' => route('api.ec.poi.download.gpx', ['id' => $id]),
            'kml' => route('api.ec.poi.download.kml', ['id' => $id]),
        ];
        switch ($format) {
            case 'gpx';
                $headers['Content-Type'] = 'application/vnd.api+json';
                $headers['Content-Disposition'] = 'attachment; filename="' . $ecPoi->id . '.gpx"';
                $content = $ecPoi->getGpx();
                $response = response()->gpx($content, 200, $headers);
                break;
            case 'kml';
                $headers['Content-Type'] = 'application/xml';
                $headers['Content-Disposition'] = 'attachment; filename="' . $ecPoi->id . '.kml"';
                $content = $ecPoi->getKml();
                $response = response()->kml($content, 200, $headers);
                break;
            default:
                $headers['Content-Type'] = 'application/vnd.api+json';
                $headers['Content-Disposition'] = 'attachment; filename="' . $ecPoi->id . '.geojson"';
                $content = $ecPoi->getGeojson($downloadUrls);
                $response = response()->json($content, 200, $headers);
                break;
        }

        return $response;
    }

    /**
     * Return EcTrack JSON.
     *
     * @param Request
     * @param int $id
     * @param array $headers
     *
     * @return Response.
     */
    public function viewEcGeojson(Request $request, int $id, array $headers = [])
    {
        $apiUrl = explode("/", request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $response = response()->json(['code' => 404, 'error' => "Not Found"], 404);

        $ec = $model::find($id);
        if (is_null($ec)) {
            return $response;
        }

        $downloadUrls = [
            'geojson' => route('api.ec.' . $apiUrl[2] . '.download.geojson', ['id' => $id]),
            'gpx' => route('api.ec.' . $apiUrl[2] . '.download.gpx', ['id' => $id]),
            'kml' => route('api.ec.' . $apiUrl[2] . '.download.kml', ['id' => $id]),
        ];
        $content = $ec->getGeojson($downloadUrls);
        $response = response()->json($content, 200, $headers);

        return $response;
    }

    /**
     * @param Request
     * @param int $id
     * @param array $headers
     *
     * @return Response.
     */
    public function viewEcGpx(Request $request, int $id, array $headers = [])
    {
        $apiUrl = explode("/", request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $response = response()->json(['code' => 404, 'error' => "Not Found"], 404);

        $ec = $model::find($id);
        if (is_null($ec)) {
            return $response;
        }

        $content = $ec->getGpx();
        $response = response()->gpx($content, 200, $headers);

        return $response;
    }

    /**
     * @param Request
     * @param int $id
     * @param array $headers
     *
     * @return Response.
     */
    public function viewEcKml(Request $request, int $id, array $headers = [])
    {
        $apiUrl = explode("/", request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $response = response()->json(['code' => 404, 'error' => "Not Found"], 404);

        $ec = $model::find($id);
        if (is_null($ec)) {
            return $response;
        }

        $content = $ec->getKml();
        $response = response()->kml($content, 200, $headers);

        return $response;
    }

    /**
     * @param Request
     * @param int $id
     *
     * @return Response.
     */
    public function downloadEcGeojson(Request $request, int $id)
    {
        $headers['Content-Type'] = 'application/vnd.api+json';
        $headers['Content-Disposition'] = 'attachment; filename="' . $id . '.geojson"';

        return $this->viewEcGeojson($request, $id, $headers);
    }

    /**
     * @param Request
     * @param int $id
     *
     * @return Response.
     */
    public function downloadEcGpx(Request $request, int $id)
    {
        $headers['Content-Type'] = 'application/xml';
        $headers['Content-Disposition'] = 'attachment; filename="' . $id . '.gpx"';

        return $this->viewEcGpx($request, $id, $headers);
    }

    /**
     * @param Request
     * @param int $id
     *
     * @return Response.
     */
    public function downloadEcKml(Request $request, int $id)
    {
        $headers['Content-Type'] = 'application/xml';
        $headers['Content-Disposition'] = 'attachment; filename="' . $id . '.kml"';

        return $this->viewEcKml($request, $id, $headers);
    }

    /**
     * get points near ecTrack in 500 meters
     *
     * @param int $idTrack the id of EcTrack
     *
     */
    public function getEcMediaNearTrack(int $idTrack)
    {
        $track = EcTrack::find($idTrack);

        $nearQuery = "SELECT ST_Distance(
        'SRID=4326;POINT(-72.1235 42.3521)'::geometry,
		'SRID=4326;LINESTRING(-72.1260 42.45, -72.123 42.1546)'::geometry
	)";

    }
}
