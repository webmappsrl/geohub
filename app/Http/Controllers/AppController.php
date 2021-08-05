<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AppController extends Controller {
    /**
     * Display the specified resource.
     *
     * @param int $id the app id in the database
     *
     * @return JsonResponse
     */
    public function config(int $id) {
        $app = App::find($id);
        if (is_null($app)) {
            return response()->json(['code' => 404, 'error' => '404 not found'], 404);
        }
        $data = [];

        // APP section
        $data['APP']['name'] = $app->name;
        $data['APP']['id'] = $app->app_id;
        $data['APP']['customerName'] = $app->customerName;

        // LANGUAGES section
        $data['LANGUAGES']['default'] = $app->default_language;
        if (isset($app->available_languages))
            $data['LANGUAGES']['available'] = json_decode($app->available_languages, true);

        // MAP section (zoom)
        $data['MAP']['defZoom'] = $app->defZoom;
        $data['MAP']['maxZoom'] = $app->maxZoom;
        $data['MAP']['minZoom'] = $app->minZoom;

        // MAP section (bbox)
        $data['MAP']['bbox'] = $this->_getBBox($app);

        // Map section layers
        $data['MAP']['layers'][0]['label'] = 'Mappa';
        $data['MAP']['layers'][0]['type'] = 'maptile';
        $data['MAP']['layers'][0]['tilesUrl'] = 'https://api.webmapp.it/tiles/';
        try {
            $data['MAP']['overlays'] = json_decode($app->external_overlays);
        } catch (\Exception $e) {
            Log::warning("The overlays in the app " . $id . " are not correctly mapped. Error: " . $e->getMessage());
        }

        // THEME section
        $data['THEME']['fontFamilyHeader'] = $app->fontFamilyHeader;
        $data['THEME']['fontFamilyContent'] = $app->fontFamilyContent;
        $data['THEME']['defaultFeatureColor'] = $app->defaultFeatureColor;
        $data['THEME']['primary'] = $app->primary;

        // OPTIONS section
        $data['OPTIONS']['baseUrl'] = 'https://geohub.webmapp.it/api/app/elbrus/' . $app->id . '/';
        $data['OPTIONS']['startUrl'] = $app->startUrl;
        $data['OPTIONS']['showEditLink'] = $app->showEditLink;
        $data['OPTIONS']['skipRouteIndexDownload'] = $app->skipRouteIndexDownload;
        $data['OPTIONS']['poiMinRadius'] = $app->poiMinRadius;
        $data['OPTIONS']['poiMaxRadius'] = $app->poiMaxRadius;
        $data['OPTIONS']['poiIconZoom'] = $app->poiIconZoom;
        $data['OPTIONS']['poiIconRadius'] = $app->poiIconRadius;
        $data['OPTIONS']['poiMinZoom'] = $app->poiMinZoom;
        $data['OPTIONS']['poiLabelMinZoom'] = $app->poiLabelMinZoom;
        $data['OPTIONS']['showTrackRefLabel'] = $app->showTrackRefLabel;

        // TABLES section
        $data['TABLES']['details']['showGpxDownload'] = $app->showGpxDownload;
        $data['TABLES']['details']['showKmlDownload'] = $app->showKmlDownload;
        $data['TABLES']['details']['showRelatedPoi'] = $app->showRelatedPoi;

        $data['TABLES']['details']['hide_duration:forward'] = !$app->table_details_show_duration_forward;
        $data['TABLES']['details']['hide_duration:backward'] = !$app->table_details_show_duration_backward;
        $data['TABLES']['details']['hide_distance'] = !$app->table_details_show_distance;
        $data['TABLES']['details']['hide_ascent'] = !$app->table_details_show_ascent;
        $data['TABLES']['details']['hide_descent'] = !$app->table_details_show_descent;
        $data['TABLES']['details']['hide_ele:max'] = !$app->table_details_show_ele_max;
        $data['TABLES']['details']['hide_ele:min'] = !$app->table_details_show_ele_min;
        $data['TABLES']['details']['hide_ele:from'] = !$app->table_details_show_ele_from;
        $data['TABLES']['details']['hide_ele:to'] = !$app->table_details_show_ele_to;
        $data['TABLES']['details']['hide_scale'] = !$app->table_details_show_scale;
        $data['TABLES']['details']['hide_cai_scale'] = !$app->table_details_show_cai_scale;
        $data['TABLES']['details']['hide_mtb_scale'] = !$app->table_details_show_mtb_scale;
        $data['TABLES']['details']['hide_ref'] = !$app->table_details_show_ref;
        $data['TABLES']['details']['hide_surface'] = !$app->table_details_show_surface;
        $data['TABLES']['details']['showGeojsonDownload'] = !!$app->table_details_show_geojson_download;
        $data['TABLES']['details']['showShapefileDownload'] = !!$app->table_details_show_shapefile_download;

        // ROUTING section
        $data['ROUTING']['enable'] = $app->enableRouting;

        // REPORT SECION
        $data['REPORTS'] = $this->_getReportSection();

        // GEOLOCATIONS SECTION
        $data['GEOLOCATION']['record']['enable'] = false;
        if ($app->geolocation_record_enable) {
            $data['GEOLOCATION']['record']['enable'] = true;
        }
        $data['GEOLOCATION']['record']['export'] = true;
        $data['GEOLOCATION']['record']['uploadUrl'] = 'https://geohub.webmapp.it/api/usergenerateddata/store';

        // AUTH section
        $data['AUTH']['showAtStartup'] = false;
        if ($app->auth_show_at_startup) {
            $data['AUTH']['showAtStartup'] = true;
        }
        $data['AUTH']['enable'] = true;
        $data['AUTH']['loginToGeohub'] = true;

        // OFFLINE section
        $data['OFFLINE']['enable'] = false;
        if ($app->offline_enable) {
            $data['OFFLINE']['enable'] = true;
        }
        $data['OFFLINE']['forceAuth'] = false;
        if ($app->offline_force_auth) {
            $data['OFFLINE']['forceAuth'] = true;
        }

        return response()->json($data, 200);
    }

    public function icon(int $id) {
        $app = App::find($id);
        if (is_null($app)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return $this->getOrDownloadIcon($app);
    }

    public function splash(int $id) {
        $app = App::find($id);
        if (is_null($app)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return $this->getOrDownloadIcon($app, 'splash');
    }

    public function iconSmall(int $id) {
        $app = App::find($id);
        if (is_null($app)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return $this->getOrDownloadIcon($app, 'icon_small');
    }

    public function featureImage(int $id) {
        $app = App::find($id);
        if (is_null($app)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return $this->getOrDownloadIcon($app, 'feature_image');
    }

    protected function getOrDownloadIcon(App $app, $type = 'icon') {
        if (!isset($app->$type)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        $pathInfo = pathinfo(parse_url($app->$type)['path']);
        if (substr($app->$type, 0, 4) === 'http') {
            header("Content-disposition:attachment; filename=$type." . $pathInfo['extension']);
            header('Content-Type:' . CONTENT_TYPE_IMAGE_MAPPING[$pathInfo['extension']]);
            readfile($app->$type);
        } else {
            //Scaricare risorsa locale
            return Storage::disk('public')->download($app->$type, $type . '.' . $pathInfo['extension']);
        }
    }

    private function _getReportSection() {
        $json_string = <<<EOT
 {
    "enable": true,
    "url": "https://geohub.webmapp.it/api/usergenerateddata/store",
    "items": [
    {
    "title": "Crea un nuovo waypoint",
    "success": "Waypoint creato con successo",
    "url": "https://geohub.webmapp.it/api/usergenerateddata/store",
    "type": "geohub",
    "fields": [
    {
    "label": "Nome",
    "name": "title",
    "mandatory": true,
    "type": "text",
    "placeholder": "Scrivi qua il nome del waypoint"
    },
    {
    "label": "Descrizione",
    "name": "description",
    "mandatory": true,
    "type": "textarea",
    "placeholder": "Descrivi brevemente il waypoint"
    },
    {
    "label": "Foto",
    "name": "gallery",
    "mandatory": false,
    "type": "gallery",
    "limit": 5,
    "placeholder": "Aggiungi qualche foto descrittiva del waypoint"
    }
    ]
    }
    ]
    }
EOT;

        return json_decode($json_string, true);
    }

    /**
     * Returns bbox array
     * [lon0,lat0,lon1,lat1]
     *
     * @param App $app
     *
     * @return array
     */
    private function _getBBox(App $app): array {
        $bbox = [];
        $q = "select ST_Extent(geometry::geometry) as bbox from ec_tracks where user_id=$app->user_id;";
        //$q = "select name,ST_AsGeojson(geometry) as bbox from ec_tracks where user_id=$app->user_id;";
        $res = DB::select($q);
        if (count($res) > 0) {
            if (!is_null($res[0]->bbox)) {
                preg_match('/\((.*?)\)/', $res[0]->bbox, $match);
                $coords = $match[1];
                $coord_array = explode(',', $coords);
                $coord_min_str = $coord_array[0];
                $coord_max_str = $coord_array[1];
                $coord_min = explode(' ', $coord_min_str);
                $coord_max = explode(' ', $coord_max_str);
                $bbox = [$coord_min[0], $coord_min[1], $coord_max[0], $coord_max[1]];
            }
        }

        return $bbox;
    }
}
