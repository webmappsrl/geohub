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
        $geometry = '{"type":"Point","coordinates":['.$poi['n7webmap_coord']['lat'].','.$poi['n7webmap_coord']['lng'].']}';
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
        // Adding POI parameters of accessibility
        if ($poi['accessibility_validity_date'])
            $this->tags['accessibility_validity_date'] = $poi['accessibility_validity_date'];
        if ($poi['accessibility_pdf'])
            $this->tags['accessibility_pdf'] = $poi['accessibility_pdf'];
        if ($poi['access_mobility_check'])
            $this->tags['access_mobility_check'] = $poi['access_mobility_check'];
        if ($poi['access_mobility_level'])
            $this->tags['access_mobility_level'] = $poi['access_mobility_level'];
        if ($poi['access_mobility_description'])
            $this->tags['access_mobility_description'] = $poi['access_mobility_description'];
        if ($poi['access_hearing_check'])
            $this->tags['access_hearing_check'] = $poi['access_hearing_check'];
        if ($poi['access_hearing_level'])
            $this->tags['access_hearing_level'] = $poi['access_hearing_level'];
        if ($poi['access_hearing_description'])
            $this->tags['access_hearing_description'] = $poi['access_hearing_description'];
        if ($poi['access_vision_check'])
            $this->tags['access_vision_check'] = $poi['access_vision_check'];
        if ($poi['access_vision_level'])
            $this->tags['access_vision_level'] = $poi['access_vision_level'];
        if ($poi['access_vision_description'])
            $this->tags['access_vision_description'] = $poi['access_vision_description'];
        if ($poi['access_cognitive_check'])
            $this->tags['access_cognitive_check'] = $poi['access_cognitive_check'];
        if ($poi['access_cognitive_level'])
            $this->tags['access_cognitive_level'] = $poi['access_cognitive_level'];
        if ($poi['access_cognitive_description'])
            $this->tags['access_cognitive_description'] = $poi['access_cognitive_description'];
        if ($poi['access_food_check'])
            $this->tags['access_food_check'] = $poi['access_food_check'];
        if ($poi['access_food_description'])
            $this->tags['access_food_description'] = $poi['access_food_description'];
            
        // Adding POI parameters of reachability
        if ($poi['reachability_by_bike_check'])
            $this->tags['reachability_by_bike_check'] = $poi['reachability_by_bike_check'];
        if ($poi['reachability_by_bike_description'])
            $this->tags['reachability_by_bike_description'] = $poi['reachability_by_bike_description'];
        if ($poi['reachability_on_foot_check'])
            $this->tags['reachability_on_foot_check'] = $poi['reachability_on_foot_check'];
        if ($poi['reachability_on_foot_description'])
            $this->tags['reachability_on_foot_description'] = $poi['reachability_on_foot_description'];
        if ($poi['reachability_by_car_check'])
            $this->tags['reachability_by_car_check'] = $poi['reachability_by_car_check'];
        if ($poi['reachability_by_car_description'])
            $this->tags['reachability_by_car_description'] = $poi['reachability_by_car_description'];
        if ($poi['reachability_by_public_transportation_check'])
            $this->tags['reachability_by_public_transportation_check'] = $poi['reachability_by_public_transportation_check'];
        if ($poi['reachability_by_public_transportation_description'])
            $this->tags['reachability_by_public_transportation_description'] = $poi['reachability_by_public_transportation_description'];

        // Adding POI parameters of general info
        if ($poi['addr:street'])
            $this->tags['addr_street'] = $poi['addr:street'];
        if ($poi['addr:housenumber'])
            $this->tags['addr_housenumber'] = $poi['addr:housenumber'];
        if ($poi['addr:postcode'])
            $this->tags['addr_postcode'] = $poi['addr:postcode'];
        if ($poi['addr:city'])
            $this->tags['addr_city'] = $poi['addr:city'];
        if ($poi['contact:phone'])
            $this->tags['contact_phone'] = $poi['contact:phone'];
        if ($poi['contact:email'])
            $this->tags['contact_email'] = $poi['contact:email'];
        if ($poi['opening_hours'])
            $this->tags['opening_hours'] = $poi['opening_hours'];
        if ($poi['capacity'])
            $this->tags['capacity'] = $poi['capacity'];
        if ($poi['stars'])
            $this->tags['stars'] = $poi['stars'];
        if ($poi['n7webmap_rpt_related_url'])
            $this->tags['related_url'] = $poi['n7webmap_rpt_related_url'];
        if ($poi['ele'])
            $this->tags['ele'] = $poi['ele'];
        if ($poi['code'])
            $this->tags['code'] = $poi['code'];
            
        // Adding POI parameters of style
        if ($poi['color'])
            $this->tags['color'] = $poi['color'];
        if ($poi['icon'])
            $this->tags['icon'] = $poi['icon'];
        if ($poi['noDetails'])
            $this->tags['noDetails'] = $poi['noDetails'];
        if ($poi['noInteraction'])
            $this->tags['noInteraction'] = $poi['noInteraction'];
        if ($poi['zindex'])
            $this->tags['zindex'] = $poi['zindex'];

    }
}