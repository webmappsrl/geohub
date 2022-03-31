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

        $data = array_merge($data, $this->config_section_app($app));
        $data = array_merge($data, $this->config_section_home($app));
        $data = array_merge($data, $this->config_section_languages($app));
        $data = array_merge($data, $this->config_section_map($app));
        $data = array_merge($data, $this->config_section_theme($app));
        $data = array_merge($data, $this->config_section_options($app));
        $data = array_merge($data, $this->config_section_tables($app));
        $data = array_merge($data, $this->config_section_routing($app));
        $data = array_merge($data, $this->config_section_report($app));
        $data = array_merge($data, $this->config_section_geolocation($app));
        $data = array_merge($data, $this->config_section_auth($app));
        $data = array_merge($data, $this->config_section_offline($app));

        return response()->json($data);
    }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_app(App $app): array {
      $data = [];

      $data['APP']['name'] = $app->name;
      $data['APP']['id'] = $app->app_id;
      $data['APP']['customerName'] = $app->customer_name;
      $data['APP']['geohubId'] = $app->id;

      return $data;
  }

  /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_home(App $app): array {
      $data = [];

      $data['HOME'][] = [
        'view'=>'title',
        'title'=>$app->name
      ];

      if($app->layers->count()>0) {
        foreach($app->layers as $layer) {
          $data['HOME'][] = [
            'view'=>'compact-horizontal',
            'title'=>$layer->title,
            'terms'=>[$layer->id]
          ];
        } 
      }

      return $data;
  }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_languages(App $app): array {
      $data['LANGUAGES']['default'] = $app->default_language;
      if (isset($app->available_languages))
          $data['LANGUAGES']['available'] = json_decode($app->available_languages, true);
        return $data;
    }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_map(App $app): array {
        $data = [];
        // MAP section (zoom)
        $data['MAP']['defZoom'] = $app->map_def_zoom;
        $data['MAP']['maxZoom'] = $app->map_max_zoom;
        $data['MAP']['minZoom'] = $app->map_min_zoom;

        if(is_null($app->map_bbox)) {
          $data['MAP']['bbox'] =$this->_getBBox($app);
        } else {
          $data['MAP']['bbox'] = json_decode($app->map_bbox,true);
        }

        // MAP section (bbox)
        if (in_array($app->api, ['elbrus'])) {
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
        }

        if($app->layers->count()>0) {
          $layers = [];
          foreach($app->layers as $layer) {
            $item=$layer->toArray();
            // style
            foreach(['color','fill_color','fill_opacity','stroke_width','stroke_opacity','zindex','line_dash'] as $field) {
              $item['style'][$field]=$item[$field];
              unset($item[$field]);
            }
            // behaviour
            foreach(['noDetails','noInteraction','minZoom','maxZoom','preventFilter','invertPolygons','alert','show_label'] as $field) {
              $item['behaviour'][$field]=$item[$field];
              unset($item[$field]);
            }
            unset($item['created_at']);
            unset($item['updated_at']);
            unset($item['app_id']);
            $layers[]=$item;
          }
          $data['MAP']['layers']=$layers;
        }
        return $data;
    }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_theme(App $app): array {
        $data = [];
        // THEME section

        $data['THEME']['fontFamilyHeader'] = $app->font_family_header;
        $data['THEME']['fontFamilyContent'] = $app->font_family_content;
        $data['THEME']['defaultFeatureColor'] = $app->default_feature_color;
        $data['THEME']['primary'] = $app->primary_color;

        return $data;
    }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_options(App $app): array {
        $data = [];
        if (in_array($app->api, ['elbrus'])) {
            // OPTIONS section
            $data['OPTIONS']['baseUrl'] = 'https://geohub.webmapp.it/api/app/elbrus/' . $app->id . '/';
        }

        $data['OPTIONS']['startUrl'] = $app->start_url;
        $data['OPTIONS']['showEditLink'] = $app->show_edit_link;
        $data['OPTIONS']['skipRouteIndexDownload'] = $app->skip_route_index_download;
        $data['OPTIONS']['poiMinRadius'] = $app->poi_min_radius;
        $data['OPTIONS']['poiMaxRadius'] = $app->poi_max_radius;
        $data['OPTIONS']['poiIconZoom'] = $app->poi_icon_zoom;
        $data['OPTIONS']['poiIconRadius'] = $app->poi_icon_radius;
        $data['OPTIONS']['poiMinZoom'] = $app->poi_min_zoom;
        $data['OPTIONS']['poiLabelMinZoom'] = $app->poi_label_min_zoom;
        $data['OPTIONS']['showTrackRefLabel'] = $app->show_track_ref_label;


        return $data;
    }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_tables(App $app): array {
        $data = [];
        if (in_array($app->api, ['elbrus'])) {
            // TABLES section
            $data['TABLES']['details']['showGpxDownload'] = !!$app->table_details_show_gpx_download;
            $data['TABLES']['details']['showKmlDownload'] = !!$app->table_details_show_kml_download;
            $data['TABLES']['details']['showRelatedPoi'] = !!$app->table_details_show_related_poi;
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
        }

        return $data;
    }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_routing(App $app): array {
        $data = [];
        if (in_array($app->api, ['elbrus'])) {
            // ROUTING section
            $data['ROUTING']['enable'] = $app->enable_routing;
        }

        return $data;
    }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_report(App $app): array {
        $data = [];
        if (in_array($app->api, ['elbrus'])) {
            // REPORT SECION
            $data['REPORTS'] = $this->_getReportSection();
        }

        return $data;
    }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_geolocation(App $app): array {
        $data = [];
        if (in_array($app->api, ['elbrus'])) {
            // GEOLOCATION SECTION
            $data['GEOLOCATION']['record']['enable'] = !!$app->geolocation_record_enable;
            $data['GEOLOCATION']['record']['export'] = true;
            $data['GEOLOCATION']['record']['uploadUrl'] = 'https://geohub.webmapp.it/api/usergenerateddata/store';
        } else {
            if (!!$app->geolocation_record_enable)
                $data['GEOLOCATION']['record']['enable'] = !!$app->geolocation_record_enable;
        }

        return $data;
    }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_auth(App $app): array {
        $data = [];
        if (in_array($app->api, ['elbrus'])) {
            // AUTH section
            $data['AUTH']['showAtStartup'] = false;
            if ($app->auth_show_at_startup) {
                $data['AUTH']['showAtStartup'] = true;
            }
            $data['AUTH']['enable'] = true;
            $data['AUTH']['loginToGeohub'] = true;
        }
        else {
          if ($app->auth_show_at_startup) {
            $data['AUTH']['enable'] = true;
            $data['AUTH']['showAtStartup'] = true;
          } else {
            $data['AUTH']['enable'] = false;
          }

        }

        return $data;
    }

    /**
     * @param App $app
     *
     * @return array
     */
    private function config_section_offline(App $app): array {
        $data = [];
        if (in_array($app->api, ['elbrus'])) {
            // OFFLINE section
            $data['OFFLINE']['enable'] = false;
            if ($app->offline_enable) {
                $data['OFFLINE']['enable'] = true;
            }
            $data['OFFLINE']['forceAuth'] = false;
            if ($app->offline_force_auth) {
                $data['OFFLINE']['forceAuth'] = true;
            }
        }

        return $data;
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

    public function iconNotify(int $id) {
        $app = App::find($id);
        if (is_null($app)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return $this->getOrDownloadIcon($app, 'icon_notify');
    }

    public function logoHomepage(int $id) {
        $app = App::find($id);
        if (is_null($app)) {
            return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        }

        return $this->getOrDownloadIcon($app, 'logo_homepage');
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
            //            if (Storage::disk('public')->exists($app->$type . '.' . $pathInfo['extension']))
            return Storage::disk('public')->download($app->$type, $type . '.' . $pathInfo['extension']);
            //            else return response()->json(['error' => 'File not found'], 404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id the app id in the database
     *
     * @return JsonResponse
     */
    public function vectorStyle(int $id) {
        $app = App::find($id);
        if (is_null($app)) {
            return response()->json(['code' => 404, 'error' => '404 not found'], 404);
        }

        $url = route('api.app.webapp.vector_layer',['id'=>$app->id]);

        $data = <<<EOF
{
  "version": 8,
  "name": "tracks",
  "metadata": {
    "maputnik:renderer": "ol"
  },
  "sources": {
    "tracks1": {
      "type": "vector",
      "url": "$url"
    }
  },
  "sprite": "",
  "glyphs": "https://orangemug.github.io/font-glyphs/glyphs/{fontstack}/{range}.pbf",
  "layers": [
    {
      "id": "EEA",
      "type": "line",
      "source": "tracks",
      "source-layer": "tracks",
      "filter": [
        "all",
        [
          "==",
          "cai_scale",
          "EEA"
        ]
      ],
      "layout": {
        "line-join": "round",
        "line-cap": "round",
        "visibility": "visible"
      },
      "paint": {
        "line-color": "rgba(255, 0, 218, 0.8)",
        "line-width": {
          "stops": [
            [
              10,
              1
            ],
            [
              20,
              10
            ]
          ]
        },
        "line-dasharray": [
          0.001,
          2
        ]
      }
    },
    {
      "id": "EE",
      "type": "line",
      "source": "tracks",
      "source-layer": "tracks",
      "filter": [
        "all",
        [
          "==",
          "cai_scale",
          "EE"
        ]
      ],
      "layout": {
        "line-join": "round",
        "line-cap": "round"
      },
      "paint": {
        "line-color": "rgba(255, 57, 0, 0.8)",
        "line-width": {
          "stops": [
            [
              10,
              1
            ],
            [
              20,
              10
            ]
          ]
        },
        "line-dasharray": [
          0.01,
          2
        ]
      }
    },
    {
      "id": "E",
      "type": "line",
      "source": "tracks",
      "source-layer": "tracks",
      "filter": [
        "all",
        [
          "==",
          "cai_scale",
          "E"
        ]
      ],
      "layout": {
        "line-join": "round",
        "line-cap": "round"
      },
      "paint": {
        "line-color": "rgba(255, 57, 0, 0.8)",
        "line-width": {
          "stops": [
            [
              10,
              1
            ],
            [
              20,
              10
            ]
          ]
        },
        "line-dasharray": [
          2,
          2
        ]
      }
    },
    {
      "id": "T",
      "type": "line",
      "source": "tracks",
      "source-layer": "tracks",
      "filter": [
        "all",
        [
          "==",
          "cai_scale",
          "T"
        ]
      ],
      "layout": {
        "line-join": "round",
        "line-cap": "round",
        "visibility": "visible"
      },
      "paint": {
        "line-color": "rgba(255, 57, 0, 0.8)",
        "line-width": {
          "stops": [
            [
              10,
              1
            ],
            [
              20,
              10
            ]
          ]
        }
      }
    },
    {
      "id": "ref",
      "type": "symbol",
      "source": "tracks",
      "source-layer": "tracks",
      "minzoom": 10,
      "maxzoom": 16,
      "layout": {
        "text-field": "{ref}",
        "visibility": "visible",
        "symbol-placement": "line",
        "text-size": 12,
        "text-allow-overlap": true
      },
      "paint": {
        "text-color": "rgba(255, 57, 0,0.8)"
      }
    }
  ],
  "id": "63fa0rhhq"
}
EOF;
        $data = json_decode($data,TRUE);
        return response()->json($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id the app id in the database
     *
     * @return JsonResponse
     */
    public function vectorLayer(int $id) {
        $app = App::find($id);
        if (is_null($app)) {
            return response()->json(['code' => 404, 'error' => '404 not found'], 404);
        }

        /**
         *
         *   "grids": [
         *       "https://tiles.webmapp.it/sentieri_toscana/{z}/{x}/{y}.grid.json"
         *    ],
         *
         */

        $tile_url="https://jidotile.webmapp.it/?x={x}&y={y}&z={z}&index=geohub_app_{$app->id}";

        $data = <<<EOF
{
  "name": "sentieri_toscana",
  "description": "",
  "legend": "",
  "attribution": "Rendered with <a href=\"https://www.maptiler.com/desktop/\">MapTiler Desktop</a>",
  "type": "baselayer",
  "version": "1",
  "format": "pbf",
  "format_arguments": "",
  "minzoom": 3,
  "maxzoom": 16,
  "bounds": [
    9.662666,
    42.59819,
    12.415403,
    44.573604
  ],
  "scale": "1.000000",
  "profile": "mercator",
  "scheme": "xyz",
  "generator": "MapTiler Desktop Plus 11.2.1-252233dc0b",
  "basename": "sentieri_toscana",
  "tiles": [
    "$tile_url"
  ],
  "tilejson": "2.0.0",
  "vector_layers": [
    {
      "id": "sentieri",
      "description": "",
      "minzoom": 3,
      "maxzoom": 16,
      "fields": {
        "id": "Number",
        "ref": "String",
        "cai_scale": "String"
      }
    }
  ]
}
EOF;

        $data = json_decode($data,TRUE);
        return response()->json($data);
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
