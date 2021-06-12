<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Http\Request;

class AppController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\App  $app
     * @return \Illuminate\Http\Response
     */
    public function config(int $id)
    {
        $app = App::find($id);
        if(is_null($app)) {
            return response()->json(['code' => 404, 'error' => '404 not found'], 404);
        }
        $data=[];

        // APP section
        $data['APP']['name']=$app->name;
        $data['APP']['id']=$app->app_id;
        $data['APP']['customerName']=$app->customerName;

        // MAP section (zoom)
        $data['MAP']['defZoom']=$app->defZoom;
        $data['MAP']['maxZoom']=$app->maxZoom;
        $data['MAP']['minZoom']=$app->minZoom;

        // Map section layers
        $data['MAP']['layers']['label']='Mappa';
        $data['MAP']['layers']['type']='maptile';
        $data['MAP']['layers']['tilesUrl']='https://api.webmapp.it/tiles/';

        // THEME section
        $data['THEME']['fontFamilyHeader']=$app->fontFamilyHeader;
        $data['THEME']['fontFamilyContent']=$app->fontFamilyContent;
        $data['THEME']['defaultFeatureColor']=$app->defaultFeatureColor;
        $data['THEME']['primary']=$app->primary;

        // OPTIONS section
        $data['OPTIONS']['baseUrl']='https://geohub.webmapp.it/api/app/elbrus/'.$app->id.'/';
        $data['OPTIONS']['startUrl']=$app->startUrl;
        $data['OPTIONS']['showEditLink']=$app->showEditLink;
        $data['OPTIONS']['skipRouteIndexDownload']=$app->skipRouteIndexDownload;
        $data['OPTIONS']['poiMinRadius']=$app->poiMinRadius;
        $data['OPTIONS']['poiMaxRadius']=$app->poiMaxRadius;
        $data['OPTIONS']['poiIconZoom']=$app->poiIconZoom;
        $data['OPTIONS']['poiIconRadius']=$app->poiIconRadius;
        $data['OPTIONS']['poiMinZoom']=$app->poiMinZoom;
        $data['OPTIONS']['poiLabelMinZoom']=$app->poiLabelMinZoom;
        $data['OPTIONS']['showTrackRefLabel']=$app->showTrackRefLabel;

        // TABLES section
        $data['TABLES']['details']['showGpxDownload']=$app->showGpxDownload;
        $data['TABLES']['details']['showKmlDownload']=$app->showKmlDownload;
        $data['TABLES']['details']['showRelatedPoi']=$app->showRelatedPoi;

        return response()->json($data, 200);
    }
}
