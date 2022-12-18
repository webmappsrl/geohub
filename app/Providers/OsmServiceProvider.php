<?php

namespace App\Providers;

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
 * TODO: implement relation
 * TODO: manage internal Exception
 * 
 * TRY ON TINKER
 * $osmp = app(\App\Providers\OsmServiceProvider::class);
 * $string = $osmp->getGeojson('node/770561143');
 * $array = $osmp->getGeojson('node/770561143',true);
 * $string = $osmp->getGeojson('way/145096288 ');
 * $array = $osmp->getGeojson('way/145096288 ',true);
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
    public function getGeojson(string $osmid, bool $retun_array=false) {
        $geojson = [];
        $geojson['version'] = 0.6;
        $geojson['generator'] = 'Laravel OsmServiceProvider by WEBMAPP';
        $geojson['_osmid'] = $osmid;
        $geojson['type']='Feature';
        $geojson['properties']=[];
        $geojson['geometry']=[];
        if($retun_array) return $geojson;
        return json_encode($geojson);
    }
}
