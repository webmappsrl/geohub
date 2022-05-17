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

    /**
     * It imports each track of the given list to the out_source_features table.
     * 
     *
     * @return int The ID of OutSourceFeature created 
     */
    public function importTrack(){
        
        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $this->endpoint.'/wp-json/wp/v2/track/'.$this->source_id;
        $track_obj = $curl->exec($url);
        $track = json_decode($track_obj,true);

        // prepare the value of tags data
        $this->prepareTrackTagsJson($track);

        // prepare feature parameters to pass to updateOrCreate function
        $this->params['geometry'] = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('".json_encode(unserialize($track['n7webmap_geojson']))."')) As wkt")[0]->wkt;
        $this->params['provider'] = get_class($this);
        $this->params['type'] = $this->type;
        $this->params['raw_data'] = json_encode($track);
        $this->params['tags'] = $this->tags;

        return $this->create_or_update_feature($this->params);
    }

    /**
     * It imports each POI of the given list to the out_source_features table.
     * 
     *
     * @return int The ID of OutSourceFeature created 
     */
    public function importPoi(){
        // Curl request to get the feature information from external source
        $curl = app(CurlServiceProvider::class);
        $url = $this->endpoint.'/wp-json/wp/v2/poi/'.$this->source_id;
        $poi_obj = $curl->exec($url);
        $poi = json_decode($poi_obj,true);
        
        // prepare the value of tags data
        $this->preparePOITagsJson($poi);
        $geometry = '{"type":"Point","coordinates":['.$poi['n7webmap_coord']['lat'].','.$poi['n7webmap_coord']['lat'].']}';
        // prepare feature parameters to pass to updateOrCreate function
        $this->params['geometry'] = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('$geometry')) As wkt")[0]->wkt;
        $this->params['provider'] = get_class($this);
        $this->params['type'] = $this->type;
        $this->params['raw_data'] = json_encode($poi);
        $this->params['tags'] = $this->tags;

        return $this->create_or_update_feature($this->params);
    }

    public function importMedia(){
        return 'getMediaList result';
    }

    /**
     * It updateOrCreate method of the class OutSourceFeature
     * 
     * @param array $params The OutSourceFeature parameters to be added or updated 
     * @return int The ID of OutSourceFeature created 
     */
    protected function create_or_update_feature(array $params) {

        $feature = OutSourceFeature::updateOrCreate(
            [
                'source_id' => $this->source_id,
                'endpoint' => $this->endpoint
            ],
            $params);
        return $feature->id;
    }

    /**
     * It populates the tags variable with the track curl information so that it can be syncronized with EcTrack 
     * 
     * @param array $track The OutSourceFeature parameters to be added or updated 
     * 
     */
    protected function prepareTrackTagsJson($track){
        $this->tags['name']['it'] = $track['title']['rendered'];
        $this->tags['description']['it'] = $track['content']['rendered'];
        $this->tags['excerpt']['it'] = $track['excerpt']['rendered'];
        if(!empty($track['wpml_translations'])) {
            foreach($track['wpml_translations'] as $lang){
                $locale = explode('_',$lang['locale']);
                $this->tags['name'][$locale[0]] = $lang['post_title'];
                // Curl request to get the feature translation from external source
                $curl = app(CurlServiceProvider::class);
                $url = $this->endpoint.'/wp-json/wp/v2/track/'.$lang['id'];
                $track_obj = $curl->exec($url);
                $track_decode = json_decode($track_obj,true);
                $this->tags['description'][$locale[0]] = $track_decode['content']['rendered'];
                $this->tags['excerpt'][$locale[0]] = $track_decode['excerpt']['rendered']; 
            }
        }
        $this->tags['from'] = $track['n7webmap_start'];
        $this->tags['to'] = $track['n7webmap_end'];
        $this->tags['ele_from'] = $track['ele:from'];
        $this->tags['ele_to'] = $track['ele:to'];
        $this->tags['ele_max'] = $track['ele:max'];
        $this->tags['ele_min'] = $track['ele:min'];
        $this->tags['distance'] = $track['distance'];
        $this->tags['difficulty'] = $track['cai_scale'];
    }
    
    /**
     * It populates the tags variable with the POI curl information so that it can be syncronized with EcPOI 
     * 
     * @param array $poi The OutSourceFeature parameters to be added or updated 
     * 
     */
    protected function preparePOITagsJson($poi){
        $this->tags['name']['it'] = $poi['title']['rendered'];
        $this->tags['description']['it'] = $poi['content']['rendered'];
        $this->tags['excerpt']['it'] = $poi['excerpt']['rendered'];
        if(!empty($poi['wpml_translations'])) {
            foreach($poi['wpml_translations'] as $lang){
                $locale = explode('_',$lang['locale']);
                $this->tags['name'][$locale[0]] = $lang['post_title']; 
                // Curl request to get the feature translation from external source
                $curl = app(CurlServiceProvider::class);
                $url = $this->endpoint.'/wp-json/wp/v2/poi/'.$lang['id'];
                $poi_obj = $curl->exec($url);
                $poi_decode = json_decode($poi_obj,true);
                $this->tags['description'][$locale[0]] = $poi_decode['content']['rendered'];
                $this->tags['excerpt'][$locale[0]] = $poi_decode['excerpt']['rendered'];
            }
        }
        //TODO: Add other POI parameters like accessibility
    }
}