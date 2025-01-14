<?php

namespace App\Http\Controllers;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    'webp' => 'image/webp',
]);

class EditorialContentController extends Controller
{
    /**
     * Calculate the model class name of a ugc from its type
     *
     * @param  string  $type  the ugc type
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
     * Get Ec image by ID
     *
     * @param  int  $id  the Ec id
     */
    public function getEcImage(int $id)
    {
        $apiUrl = explode('/', request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ec = $model::find($id);
        if (is_null($ec)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        // https://wmptest.s3.eu-central-1.amazonaws.com/EcMedia/2.jpg

        /*if (preg_match('/\.amazonaws\.com\//', $ec->url)) {
            $explode = explode('.amazonaws.com/', $ec->url);
            $url = end($explode);
            Log::info($url);
            return Storage::disk('s3')->download($url, 'name' . '.jpg');
        }*/
        $pathInfo = pathinfo($ec->path);
        if (substr($ec->url, 0, 4) === 'http') {
            header('Content-disposition:attachment; filename=name.'.$pathInfo['extension']);
            header('Content-Type:'.CONTENT_TYPE_IMAGE_MAPPING[$pathInfo['extension']]);
            readfile($ec->url);
        } else {
            // Scaricare risorsa locale
            if (isset($pathInfo['extension'])) {
                return Storage::disk('public')->download($ec->url, 'name.'.$pathInfo['extension']);
            } else {
                return Storage::disk('public')->download($ec->url, 'name');
            }
        }
    }

    /** Update the ec media with new data from Geomixer
     *
     * @param  Request  $request  the request with data from geomixer POST
     * @param  int  $id  the id of the EcMedia
     */
    public function updateEcPoi(Request $request, $id)
    {
        $ecPoi = EcPoi::find($id);

        if (is_null($ecPoi)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        if (
            ! is_null($request->geometry)
            && is_array($request->geometry)
            && isset($request->geometry['type'])
            && isset($request->geometry['coordinates'])
        ) {
            $ecPoi->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('".json_encode($request->geometry)."'))");
        }

        // TODO: Removed this part. ELE is now calculated by UpdateEcPoiDemJob
        // if ($ecPoi->skip_geomixer_tech == false) {
        //     $fields = [
        //         'ele',
        //     ];
        //     foreach ($fields as $field) {
        //         if (isset($request->$field)) {
        //             $ecPoi->$field = $request->$field;
        //         }
        //     }
        // }

        if (! empty($request->where_ids)) {
            $ecPoi->taxonomyWheres()->sync($request->where_ids);
        }

        $ecPoi->skip_update = true;
        $ecPoi->save();
    }

    /**
     * Return geometry formatted by $format.
     *
     * @param  Request  $request  the request with data from geomixer POST
     * @return mixed
     */
    public function downloadEcPoi(Request $request, int $id, string $format = 'geojson')
    {
        $ecPoi = EcPoi::find($id);

        if (is_null($ecPoi)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        $headers = [];
        $downloadUrls = [
            'geojson' => route('api.ec.poi.download.geojson', ['id' => $id]),
            'gpx' => route('api.ec.poi.download.gpx', ['id' => $id]),
            'kml' => route('api.ec.poi.download.kml', ['id' => $id]),
        ];
        switch ($format) {
            case 'gpx':
                $headers['Content-Type'] = 'application/vnd.api+json';
                $headers['Content-Disposition'] = 'attachment; filename="'.$ecPoi->id.'.gpx"';
                $content = $ecPoi->getGpx();
                $response = response()->gpx($content, 200, $headers);
                break;
            case 'kml':
                $headers['Content-Type'] = 'application/xml';
                $headers['Content-Disposition'] = 'attachment; filename="'.$ecPoi->id.'.kml"';
                $content = $ecPoi->getKml();
                $response = response()->kml($content, 200, $headers);
                break;
            default:
                $headers['Content-Type'] = 'application/vnd.api+json';
                $headers['Content-Disposition'] = 'attachment; filename="'.$ecPoi->id.'.geojson"';
                $content = $ecPoi->getGeojson($downloadUrls);
                $response = response()->json($content, 200, $headers);
                break;
        }

        return $response;
    }

    /**
     * Return EcTrack JSON.
     */
    public function viewEcGeojson(Request $request, int $id, array $headers = []): JsonResponse
    {
        $apiUrl = explode('/', request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ec = $model::find($id);
        if (is_null($ec)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }
        if (! empty($headers)) {
            $headers = $this->createDownloadFileName($ec, $headers);
        }

        return response()->json($ec->getGeojson(), 200, $headers);
    }

    /**
     * @return mixed
     */
    public function viewEcGpx(Request $request, int $id, array $headers = [])
    {
        $apiUrl = explode('/', request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ec = $model::find($id);
        if (is_null($ec)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        $content = $ec->getGpx();
        $headers = $this->createDownloadFileName($ec, $headers);

        return response()->gpx($content, 200, $headers);
    }

    /**
     * @return mixed
     */
    public function viewEcKml(Request $request, int $id, array $headers = [])
    {
        $apiUrl = explode('/', request()->path());
        try {
            $model = $this->_getEcModelFromType($apiUrl[2]);
        } catch (Exception $e) {
            return response()->json(['code' => 400, 'error' => $e->getMessage()], 400);
        }

        $ec = $model::find($id);
        if (is_null($ec)) {
            return response()->json(['code' => 404, 'error' => 'Not Found'], 404);
        }

        $content = $ec->getKml();
        $headers = $this->createDownloadFileName($ec, $headers);

        return response()->kml($content, 200, $headers);
    }

    public function downloadEcGeojson(Request $request, int $id): JsonResponse
    {
        $headers['Content-Type'] = 'application/vnd.api+json';
        $headers['Content-Disposition'] = 'attachment; filename="'.$id.'.geojson"';

        return $this->viewEcGeojson($request, $id, $headers);
    }

    /**
     * @return mixed
     */
    public function downloadEcGpx(Request $request, int $id)
    {
        $headers['Content-Type'] = 'application/xml';
        $headers['Content-Disposition'] = 'attachment; filename="'.$id.'.gpx"';

        return $this->viewEcGpx($request, $id, $headers);
    }

    /**
     * @return mixed
     */
    public function downloadEcKml(Request $request, int $id)
    {
        $headers['Content-Type'] = 'application/xml';
        $headers['Content-Disposition'] = 'attachment; filename="'.$id.'.kml"';

        return $this->viewEcKml($request, $id, $headers);
    }

    /**
     * @param  Model  $ec
     * @param  array  $headers
     * @return array
     */
    public function createDownloadFileName($ec, $headers)
    {
        $fileName = '';
        if ($ec->ref) {
            $fileName = $ec->ref;
        }
        if (empty($ec->ref) && $ec->name) {
            $fileName = $ec->name;
        }
        if ($fileName) {
            $originalFileName = $headers['Content-Disposition'];
            $extension = trim(pathinfo($originalFileName, PATHINFO_EXTENSION), '"');

            $headers['Content-Disposition'] = 'attachment; filename="'.$fileName.'.'.$extension.'"';
        }

        return $headers;
    }
}
