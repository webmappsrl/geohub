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
     *                     description="FeatureCollection geojson",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="features",
     *                     description="An array of features",
     *                     type="object",
     *                     @OA\Property( type="object",
     *                       @OA\Property( property="type", type="string",  description="Feature"),
     *                      @OA\Property(
     *                     property="properties",
     *                     type="object",
     *                     @OA\Property( property="id", type="integer",  description="Internal POI ID"),
     *                     @OA\Property( property="created_at", type="date",  description="Creation date"),
     *                     @OA\Property( property="name", type="object",  description="Name of the feature in different languages"),
     *                     @OA\Property( property="description", type="object",  description="Description of the feature in different languages"),
     *                     @OA\Property( property="excerpt", type="object",  description="Excerpt of the feature in different languages"),
     *                     @OA\Property( property="user_id", type="integer",  description="Internal ID of the owner user"),
     *                     @OA\Property( property="out_source_feature_id", type="integer",  description="Internal ID of the corrispondent feature in OSF table"),
     *                     @OA\Property( property="noDetails", type="boolean",  description=""),
     *                     @OA\Property( property="noInteraction", type="boolean",  description=""),
     *                     @OA\Property( property="access_mobility_check", type="boolean",  description=""),
     *                     @OA\Property( property="access_hearing_check", type="boolean",  description=""),
     *                     @OA\Property( property="access_vision_check", type="boolean",  description=""),
     *                     @OA\Property( property="access_cognitive_check", type="boolean",  description=""),
     *                     @OA\Property( property="access_food_check", type="boolean",  description=""),
     *                     @OA\Property( property="reachability_by_bike_check", type="boolean",  description=""),
     *                     @OA\Property( property="reachability_on_foot_check", type="boolean",  description=""),
     *                     @OA\Property( property="reachability_by_car_check", type="boolean",  description=""),
     *                     @OA\Property( property="reachability_by_public_transportation_check", type="boolean",  description=""),
     *                     @OA\Property( property="geojson_url", type="string",  description="Geojson API of the feature"),
     *                     @OA\Property( property="gpx_url", type="string",  description="GPX API of the feature"),
     *                     @OA\Property( property="kml_url", type="string",  description="KML API of the feature"),
     *                     @OA\Property( property="taxonomy", type="object",  description="array of taxonomies related to this feature with internal taxonomy ID"),
     *                 ),
     *                 @OA\Property(property="geometry", type="object",
     *                      @OA\Property( property="type", type="string",  description="Postgis geometry type: POINT"),
     *                      @OA\Property( property="coordinates", type="object",  description="POINT coordinates (WGS84)")
     *                      ) 
     *                 ),
     *                  
     *                 ),
     *                 example={"type":"FeatureCollection","features":{{"type":"Feature","properties":{"id":531,"created_at":"2022-06-23T08:36:21.000000Z","updated_at":"2022-07-04T16:37:27.000000Z","name":{"it":"MERCADO DE LA LONJA DEL BARRANCO","de":null},"description":{"en":"<p>This building was conceived to host the Fish Market of Seville","it":"<p>Questo edificio è stato concepito per ospitare il mercato del pesce di Siviglia"},"excerpt":{"en":"This building was conceived to host the Fish Market","it":"Questo edificio è stato concepito per ospitare il mercato"},"user_id":17455,"out_source_feature_id":3263,"noDetails":false,"noInteraction":false,"access_mobility_check":false,"access_hearing_check":false,"access_vision_check":false,"access_cognitive_check":false,"access_food_check":false,"reachability_by_bike_check":false,"reachability_on_foot_check":false,"reachability_by_car_check":false,"reachability_by_public_transportation_check":false,"geojson_url":"https://geohub.webmapp.it/api/ec/poi/download/531.geojson","gpx_url":"https://geohub.webmapp.it/api/ec/poi/download/531.gpx","kml_url":"https://geohub.webmapp.it/api/ec/poi/download/531.kml","taxonomy":{"theme":{9},"poi_type":{35,37,38,39,47,49}}},"geometry":{"type":"Point","coordinates":{-6.002129316,37.387627623}}}}}
     *             )
     *         )   
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The internal ID of the APP",
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
