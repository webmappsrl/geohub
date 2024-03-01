<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AppController extends Controller
{

  public function icon(int $id)
  {
    $app = App::find($id);
    if (is_null($app)) {
      return response()->json(['code' => 404, 'error' => "Not Found"], 404);
    }

    return $this->getOrDownloadIcon($app);
  }

  public function splash(int $id)
  {
    $app = App::find($id);
    if (is_null($app)) {
      return response()->json(['code' => 404, 'error' => "Not Found"], 404);
    }

    return $this->getOrDownloadIcon($app, 'splash');
  }

  public function iconSmall(int $id)
  {
    $app = App::find($id);
    if (is_null($app)) {
      return response()->json(['code' => 404, 'error' => "Not Found"], 404);
    }

    return $this->getOrDownloadIcon($app, 'icon_small');
  }

  public function featureImage(int $id)
  {
    $app = App::find($id);
    if (is_null($app)) {
      return response()->json(['code' => 404, 'error' => "Not Found"], 404);
    }

    return $this->getOrDownloadIcon($app, 'feature_image');
  }

  public function iconNotify(int $id)
  {
    $app = App::find($id);
    if (is_null($app)) {
      return response()->json(['code' => 404, 'error' => "Not Found"], 404);
    }

    return $this->getOrDownloadIcon($app, 'icon_notify');
  }

  public function logoHomepage(int $id)
  {
    $app = App::find($id);
    if (is_null($app)) {
      return response()->json(['code' => 404, 'error' => "Not Found"], 404);
    }

    return $this->getOrDownloadIcon($app, 'logo_homepage');
  }

  protected function getOrDownloadIcon(App $app, $type = 'icon')
  {
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
  public function vectorStyle(int $id)
  {
    $app = App::find($id);
    if (is_null($app)) {
      return response()->json(['code' => 404, 'error' => '404 not found'], 404);
    }

    $url = route('api.app.webapp.vector_layer', ['id' => $app->id]);

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
    $data = json_decode($data, TRUE);
    return response()->json($data);
  }

  /**
   * Display the specified resource.
   *
   * @param int $id the app id in the database
   *
   * @return JsonResponse
   */
  public function vectorLayer(int $id)
  {
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

    $tile_url = "https://jidotile.webmapp.it/?x={x}&y={y}&z={z}&index=geohub_app_{$app->id}";

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

    $data = json_decode($data, TRUE);
    return response()->json($data);
  }

  public function config(int $id)
  {
    $app = App::find($id);
    if (is_null($app)) {
      return response()->json(['code' => 404, 'error' => '404 not found'], 404);
    }
    $confUri = $id . ".json";
    if (Storage::disk('conf')->exists($confUri)) {
      $json = Storage::disk('conf')->get($confUri);
      return response()->json(json_decode($json));
    } else {
      $json = $app->BuildConfJson($id);
      return response()->json($json);
    }
  }
}
