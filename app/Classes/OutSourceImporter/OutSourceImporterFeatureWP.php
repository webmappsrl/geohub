<?php

namespace App\Classes\OutSourceImporter;

use App\Helpers\OutSourceImporterHelper;
use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use Illuminate\Support\Facades\DB;

class OutSourceImporterFeatureWP extends OutSourceImporterFeatureAbstract { 

    // DATA array
    protected array $params;
    protected array $tags;

    public function importTrack(){
        
        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $this->endpoint.'/wp-json/wp/v2/track/'.$this->source_id;
        $track_obj = $curl->exec($url);
        $track = json_decode($track_obj,true);

        // prepare the value of tags data
        $this->prepareTagsJson($track);

        // prepare feature parameters to pass to updateOrCreate function
        $this->params['geometry'] = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('".json_encode(unserialize($track['n7webmap_geojson']))."')) As wkt")[0]->wkt;
        $this->params['provider'] = get_class($this);
        $this->params['type'] = $this->type;
        $this->params['raw_data'] = json_encode($track);
        $this->params['tags'] = $this->tags;

        return $this->create_or_update_feature($this->params);
    }

    public function importPoi(){
        return 'getPoiList result';
    }

    public function importMedia(){
        return 'getMediaList result';
    }

    protected function create_or_update_feature(array $params) {

        $feature = OutSourceFeature::updateOrCreate(
            [
                'source_id' => $this->source_id,
                'endpoint' => $this->endpoint
            ],
            $params);
        return $feature->id;
    }

    protected function prepareTagsJson($track){
        $this->tags['name']['it'] = $track['title']['rendered'];
        if(!empty($track['wpml_translations'])) {
            foreach($track['wpml_translations'] as $lang){
                if ($lang['locale'] == 'en_US') {
                    $this->tags['name']['en'] = $lang['post_title']; 
                }
            }
        }
        $this->tags['description']['it'] = $track['content']['rendered'];
        $this->tags['excerpt']['it'] = $track['excerpt']['rendered'];
        $this->tags['from'] = $track['n7webmap_start'];
        $this->tags['to'] = $track['n7webmap_end'];
        $this->tags['ele_from'] = $track['ele:from'];
        $this->tags['ele_to'] = $track['ele:to'];
        $this->tags['ele_max'] = $track['ele:max'];
        $this->tags['ele_min'] = $track['ele:min'];
        $this->tags['distance'] = $track['distance'];
        $this->tags['difficulty'] = $track['cai_scale'];
    }
}