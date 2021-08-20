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

class EditorialContentController extends Controller {
    /**
     * Calculate the model class name of a ugc from its type
     *
     * @param string $type the ugc type
     *
     * @return string the model class name
     *
     * @throws Exception
     */
    private function _getEcModelFromType(string $type): string {
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
     * Get Ec image by ID
     *
     * @param int $id the Ec id
     *
     *
     */
    public function getEcImage(int $id) {
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
     * @param int     $id      the id of the EcMedia
     */
    public function updateEcMedia(Request $request, $id) {
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
            $url = $request->url;
            if (isset($url) && substr($url, 0, 4) === 'http') {
                $headers = get_headers($request->url);

                if (stripos($headers[0], "200 OK") >= 0)
                    Storage::disk('public')->delete($actualUrl);
            }
        } catch (Exception $e) {
            Log::warning($e->getMessage());
        }
    }

    /** Update the ec media with new data from Geomixer
     *
     * @param Request $request the request with data from geomixer POST
     * @param int     $id      the id of the EcMedia
     */
    public function updateEcPoi(Request $request, $id) {
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
     * @param int     $id
     * @param string  $format
     *
     * @return mixed
     */
    public function downloadEcPoi(Request $request, int $id, string $format = 'geojson') {
        $ecPoi = EcPoi::find($id);

        if (is_null($ecPoi))
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);

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
     * @param Request $request
     * @param int     $id
     * @param array   $headers
     *
     * @return JsonResponse
     */
    public function viewEcGeojson(Request $request, int $id, array $headers = []): JsonResponse {
        $apiUrl = explode("/", request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ec = $model::find($id);
        if (is_null($ec))
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);;

        return response()->json($ec->getGeojson(), 200, $headers);
    }

    /**
     * @param Request $request
     * @param int     $id
     * @param array   $headers
     *
     * @return mixed
     */
    public function viewEcGpx(Request $request, int $id, array $headers = []) {
        $apiUrl = explode("/", request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ec = $model::find($id);
        if (is_null($ec))
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);

        $content = $ec->getGpx();

        return response()->gpx($content, 200, $headers);
    }

    /**
     * @param Request $request
     * @param int     $id
     * @param array   $headers
     *
     * @return mixed
     */
    public function viewEcKml(Request $request, int $id, array $headers = []) {
        $apiUrl = explode("/", request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ec = $model::find($id);
        if (is_null($ec))
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);

        $content = $ec->getKml();

        return response()->kml($content, 200, $headers);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function downloadEcGeojson(Request $request, int $id): JsonResponse {
        $headers['Content-Type'] = 'application/vnd.api+json';
        $headers['Content-Disposition'] = 'attachment; filename="' . $id . '.geojson"';

        return $this->viewEcGeojson($request, $id, $headers);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return mixed
     */
    public function downloadEcGpx(Request $request, int $id) {
        $headers['Content-Type'] = 'application/xml';
        $headers['Content-Disposition'] = 'attachment; filename="' . $id . '.gpx"';

        return $this->viewEcGpx($request, $id, $headers);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return mixed
     */
    public function downloadEcKml(Request $request, int $id) {
        $headers['Content-Type'] = 'application/xml';
        $headers['Content-Disposition'] = 'attachment; filename="' . $id . '.kml"';

        return $this->viewEcKml($request, $id, $headers);
    }
}
