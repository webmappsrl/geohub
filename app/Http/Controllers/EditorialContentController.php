<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\User;
use \App\Models\TaxonomyWhere;
use App\Providers\HoquServiceProvider;
use App\Traits\GeometryFeatureTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class EditorialContentController extends Controller
{
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
    public function getEcjson(int $id): JsonResponse
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

        return response()->json($ec);
    }

    /**
     * Get Ec image by ID
     *
     * @param int $id the Ec id
     *
     * @return JsonResponse return the Ec Image
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

        $headers = array();
        $imagePath = public_path() . '/storage/' . $ec->url;
        return Storage::disk('public')->download($ec->url, 'name' . '.jpg');
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

        if (!is_null($request->geometry))
            $ecMedia->geometry = DB::raw("public.ST_Force2D(public.ST_GeomFromGeojson('" . json_encode($request->geometry) . "'))");

        if (!empty($request->where_ids)) {
            $ecMedia->taxonomyWheres()->sync($request->where_ids);
        }
        $ecMedia->save();
        if (Storage::disk('s3')->exists($request->url))
            Storage::disk('public')->delete($actualUrl);
    }

}
