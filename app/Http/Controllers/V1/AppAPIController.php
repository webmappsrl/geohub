<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use Illuminate\Http\Request;

class AppAPIController extends Controller
{
    /**
     * @OA\Tag(
     *     name="pois",
     *     description="Feature Collection",
     * )
     * 
     * @OA\Get(
     *      path="/api/v1/app/{id}/pois.geojson",
     *      tags={"pois"},
     *      @OA\Response(
     *          response=200,
     *          description="Returns a Feature Collection of pois related to the app by Theme taxonomy",
     *      @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="type",
     *                     description="Geojson type",
     *                     type="string"
     *                 ),
     *                  @OA\Property(
     *                     property="properties",
     *                     type="object",
     *                     @OA\Property( property="id", type="integer",  description="OSM2CAI ID"),
     *                     @OA\Property( property="relation_ID", type="integer",  description="OSMID"),
     *                     @OA\Property( property="source", type="string",  description="from SDA=3 and over must be survey:CAI or other values accepted by CAI as valid source"),
     *                     @OA\Property( property="cai_scale", type="string",  description="CAI scale difficulty: T E EE"),
     *                     @OA\Property( property="from", type="string",  description="start point"),
     *                     @OA\Property( property="to", type="string",  description="end point"),
     *                     @OA\Property( property="ref", type="string",  description="local ref hiking route number must be three number and a letter only in last position for variants"),
     *                     @OA\Property( property="sda", type="integer",  description="stato di accatastamento")
     *                 ),
     *                 @OA\Property(property="geometry", type="object",
     *                      @OA\Property( property="type", type="point",  description="Postgis geometry types: LineString, MultiLineString"),
     *                      @OA\Property( property="coordinates", type="object",  description="hiking routes coordinates (WGS84)")
     *                 ),
     *                 example={"type":"FeatureCollection","features":{}}
     *             )
     *         )   
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The ID of the APP",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     * )
     * 
     */
    public function pois(int $id) {
        $app = App::find($id);
        if (is_null($app)) {
            return response()->json(['code' => 404, 'error' => '404 not found'], 404);
        }
  
        $data = [
          "type" => "FeatureCollection",
        ];
  
        $data['features'] = $app->getAllPoisGeojson();
        return response()->json($data);
    }
}
