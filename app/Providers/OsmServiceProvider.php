<?php

namespace App\Providers;

use Exception;
use Illuminate\Support\ServiceProvider;

/**
 * General purpose OpenStreetMap Service provider.
 * 
 * Based on OSM V0.6 API: https://wiki.openstreetmap.org/wiki/API_v0.6
 * This service provider can be used to obtain geojson format for node, way and relation from
 * OpenStreetMap.
 * 
 * IMPORTANT NOTE: on laravel 8.X if you use this provider remember to activate
 * on config/app.php:
 * 
 *  'providers' => [
 *         ...
 *         App\Providers\OsmServiceProvider::class,
 *         ...,
 *         ]
 * 
 * 
 * Useful examples:
 * NODE:
 * OSM: https://openstreetmap.org/node/770561143 
 * XML: https://api.openstreetmap.org/api/0.6/node/770561143 
 * JSON: https://api.openstreetmap.org/api/0.6/node/770561143.json 
 * 
 * WAY:
 * OSM: https://openstreetmap.org/way/145096288 
 * XML: https://api.openstreetmap.org/api/0.6/way/145096288 
 * XMLFULL: https://api.openstreetmap.org/api/0.6/way/145096288/full 
 * JSON: https://api.openstreetmap.org/api/0.6/way/145096288.json 
 * JSONFULL: https://api.openstreetmap.org/api/0.6/way/145096288/full.json 
 * 
 * RELATION:
 * OSM: https://openstreetmap.org/relation/12312405 
 * XML: https://api.openstreetmap.org/api/0.6/relation/12312405 
 * XMLFULL: https://api.openstreetmap.org/api/0.6/relation/12312405/full 
 * JSON: https://api.openstreetmap.org/api/0.6/relation/12312405.json 
 * JSONFULL: https://api.openstreetmap.org/api/0.6/relation/12312405/full.json 
 * 
 * TODO: implement node
 * TODO: implement way
 * TODO: implement relation
 * TODO: manage internal Exception
 * 
 * TRY ON TINKER
 * $osmp = app(\App\Providers\OsmServiceProvider::class);
 * $array = json_decode($osmp->getGeojson('node/770561143'),true);
 * $array = json_decode($osmp->getGeojson('way/145096288 '),true);
 * 
 */
class OsmServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(OsmServiceProvider::class, function ($app) {
            return new OsmServiceProvider($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Undocumented function
     *
     * @param string $osmid Osmid string with type: node/[id], way/[id], relation/[id]
     * @param boolean $retun_array set it as true if you want return value as array
     */
    public function getGeojson(string $osmid):string {

        if(!$this->checkOsmId($osmid)) {
            throw new Exception('Invalid osmid '.$osmid);
        }

        $geojson = [];
        $geojson['version'] = 0.6;
        $geojson['generator'] = 'Laravel OsmServiceProvider by WEBMAPP';
        $geojson['_osmid'] = $osmid;
        $geojson['type']='Feature';

        $geojson['_api_url'] = $this->getFullOsmApiUrlByOsmId($osmid);

        $props_and_geom = $this->getPropertiesAndGeometry($osmid);
        $geojson['properties']=$props_and_geom[0];
        $geojson['geometry']=$props_and_geom[1];
  
        return json_encode($geojson);
    }

    /**
     * Returns the URL OSM v06 JSON API string (full form way and relation)
     *
     * @param [type] $osmid
     * @return string
     */   
     // TODO: test it!
    private function getFullOsmApiUrlByOsmId($osmid): string {
        $url = 'https://api.openstreetmap.org/api/0.6/'.$osmid;
        if(preg_match('/node/',$osmid)){
            $url = $url . '.json';
        } 
        else {
            // way and relation directly call full.json
            $url = $url . '/full.json';
        }
        return $url;
    }

    /**
     * Return true if osmid is valid: node/[id], way/[id], relation/[id]
     *
     * @param string $osmid
     * @return boolean true if is valid false otherwise
     */
    // TODO: test it!
    public function checkOsmId(string $osmid):bool {
        if (preg_match('#^node/\d+$#',$osmid)==1) return true;
        if (preg_match('#^way/\d+$#',$osmid)==1) return true;
        if (preg_match('#^relation/\d+$#',$osmid)==1) return true;
        return false;
    }

    private function getPropertiesAndGeometry($osmid):array {
        $json = json_decode($this->execCurl($this->getFullOsmApiUrlByOsmId($osmid)),true);
        if(preg_match('/node/',$osmid)) {
            return $this->getPropertiesAndGeometryForNode($json);
        }
        else if(preg_match('/way/',$osmid)) {
            return $this->getPropertiesAndGeometryForWay($json);
        }
        else if(preg_match('/relation/',$osmid)) {
            return $this->getPropertiesAndGeometryForRelation($json);
        }
        else {
            throw new Exception('OSMID has not vali type (node,way,relation) '.$osmid);
        }
        return [];
    }

    // TODO: test it!
    private function getPropertiesAndGeometryForNode($json):array {
        // TODO: manage exception with empty elements or no tags
        $properties = $json['elements'][0]['tags'];
        $geometry = [];
        return [$properties,$geometry];
    }

    // TODO: test it!
    private function getPropertiesAndGeometryForWay($json):array {
        $properties = [];
        $geometry = [];
        return [$properties,$geometry];
    }

    // TODO: test it!
    private function getPropertiesAndGeometryForRelation($json):array {
        $properties = [];
        $geometry = [];
        return [$properties,$geometry];
    }

    private function execCurl($url):string {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET'
        ));

        $response = curl_exec($curl);

        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode == 200) {
            return $response;
        }
        throw new Exception('Invalid CURL request exit with code '.$httpcode);
    }
}
