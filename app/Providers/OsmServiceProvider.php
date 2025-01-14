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
 * TODO: implement relation
 * TODO: Exception remove all generic relation (throw new Exception) with specific Exception and
 *       update test with specific Exception
 *
 * TRY ON TINKER
 * $osmp = app(\App\Providers\OsmServiceProvider::class);
 * $s = $osmp->getGeojson('node/770561143');
 * $s = $osmp->getGeojson('way/145096288 ');
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
     * @param  string  $osmid  Osmid string with type: node/[id], way/[id], relation/[id]
     * @param  bool  $retun_array  set it as true if you want return value as array
     */
    public function getGeojson(string $osmid): string
    {

        if (! $this->checkOsmId($osmid)) {
            throw new Exception('Invalid osmid '.$osmid);
        }

        $geojson = [];
        $geojson['version'] = 0.6;
        $geojson['generator'] = 'Laravel OsmServiceProvider by WEBMAPP';
        $geojson['_osmid'] = $osmid;
        $geojson['type'] = 'Feature';

        $geojson['_api_url'] = $this->getFullOsmApiUrlByOsmId($osmid);

        $props_and_geom = $this->getPropertiesAndGeometry($osmid);
        $geojson['properties'] = $props_and_geom[0];
        $geojson['geometry'] = $props_and_geom[1];

        return json_encode($geojson);
    }

    /**
     * Returns the URL OSM v06 JSON API string (full form way and relation)
     *
     * @param [type] $osmid
     */
    public function getFullOsmApiUrlByOsmId($osmid): string
    {
        $url = 'https://api.openstreetmap.org/api/0.6/'.$osmid;
        if (preg_match('/node/', $osmid)) {
            $url = $url.'.json';
        } else {
            // way and relation directly call full.json
            $url = $url.'/full.json';
        }

        return $url;
    }

    /**
     * Return true if osmid is valid: node/[id], way/[id], relation/[id]
     *
     * @return bool true if is valid false otherwise
     */
    public function checkOsmId(string $osmid): bool
    {
        if (preg_match('#^node/\d+$#', $osmid) == 1) {
            return true;
        }
        if (preg_match('#^way/\d+$#', $osmid) == 1) {
            return true;
        }
        if (preg_match('#^relation/\d+$#', $osmid) == 1) {
            return true;
        }

        return false;
    }

    public function getPropertiesAndGeometry($osmid): array
    {
        $curl = app(CurlServiceProvider::class);
        $url = $this->getFullOsmApiUrlByOsmId($osmid);
        $json = json_decode($curl->exec($url), true);
        if (! array_key_exists('elements', $json)) {
            throw new Exception("Response from OSM has something wrong: check it out with $url.", 1);
        }
        if (preg_match('/node/', $osmid)) {
            return $this->getPropertiesAndGeometryForNode($json);
        } elseif (preg_match('/way/', $osmid)) {
            return $this->getPropertiesAndGeometryForWay($json);
        } elseif (preg_match('/relation/', $osmid)) {
            return $this->getPropertiesAndGeometryForRelation($json);
        } else {
            throw new Exception('OSMID has not vali type (node,way,relation) '.$osmid);
        }

        return [];
    }

    private function getPropertiesAndGeometryForNode(array $json): array
    {
        if (! isset($json['elements'][0]['tags'])) {
            throw new Exception('JSON from OSM has no tags', 1);
        }
        if (! isset($json['elements'][0]['lat'])) {
            throw new Exception('JSON from OSM has no lat', 1);
        }
        if (! isset($json['elements'][0]['lon'])) {
            throw new Exception('JSON from OSM has no lon', 1);
        }
        $properties = $json['elements'][0]['tags'];
        $geometry = [
            'type' => 'Point',
            'coordinates' => [
                $json['elements'][0]['lon'],
                $json['elements'][0]['lat'],
            ],
        ];
        $properties['_updated_at'] = $this->getUpdatedAt($json);

        return [$properties, $geometry];
    }

    private function getPropertiesAndGeometryForWay(array $json): array
    {
        $nodes_full = [];
        $nodes = [];
        $properties = [];
        $geometry = [];
        $coordinates = [];

        // Loop on elements
        foreach ($json['elements'] as $element) {
            if ($element['type'] == 'node') {
                if (! array_key_exists('lon', $element)) {
                    throw new OsmServiceProviderExceptionNodeHasNoLon('No lon (longitude) found', 1);
                }
                if (! array_key_exists('lat', $element)) {
                    throw new OsmServiceProviderExceptionNodeHasNoLat('No lat (latitude) found', 1);
                }
                $nodes_full[$element['id']] = [
                    $element['lon'],
                    $element['lat'],
                ];
            } elseif ($element['type'] == 'way') {
                if (! array_key_exists('tags', $element)) {
                    throw new OsmServiceProviderExceptionNoTags('No tags found in way', 1);
                }
                $properties = $element['tags'];
                if (! array_key_exists('nodes', $element)) {
                    throw new OsmServiceProviderExceptionWayHasNoNodes('No nodes found in way', 1);
                }
                $nodes = $element['nodes'];
            }
        }

        // Build Geometry
        foreach ($nodes as $id) {
            $coordinates[] = $nodes_full[$id];
        }
        $geometry['type'] = 'LineString';
        $geometry['coordinates'] = $coordinates;
        $properties['_updated_at'] = $this->getUpdatedAt($json);

        return [$properties, $geometry];
    }

    // TODO: implement and test it!
    private function getPropertiesAndGeometryForRelation($json): array
    {
        $properties = [];
        $geometry = [];

        return [$properties, $geometry];
    }

    /**
     * It returns the REAL updated_at time for a OSM feature. Where real means the most recent element
     * that builds up the feature. For example if a relation has timestamp value 01-01-2000 and one of
     * way member has timestamp 01-01-2001 the return value will be 01-01-2001 and NOT 01-01-2000
     *
     * @param  array  $json  Json array response from node/way/relation full API (v06)
     */
    public function getUpdatedAt(array $json): string
    {
        if (! array_key_exists('elements', $json)) {
            throw new Exception('Json ARRAY has not elements key, something is wrong.', 1);
        }
        $updated_at = [];
        foreach ($json['elements'] as $element) {
            if (! array_key_exists('timestamp', $element)) {
                throw new Exception('An element has no TIMESTAMP key', 1);
            }
            $updated_at[] = strtotime($element['timestamp']);
        }

        return date('Y-m-d H:i:s', max($updated_at));
    }
}

class OsmServiceProviderException extends Exception {}
class OsmServiceProviderExceptionNoElements extends OsmServiceProviderException {}
class OsmServiceProviderExceptionNoTags extends OsmServiceProviderException {}
class OsmServiceProviderExceptionWayHasNoNodes extends OsmServiceProviderException {}
class OsmServiceProviderExceptionNodeHasNoLat extends OsmServiceProviderException {}
class OsmServiceProviderExceptionNodeHasNoLon extends OsmServiceProviderException {}
