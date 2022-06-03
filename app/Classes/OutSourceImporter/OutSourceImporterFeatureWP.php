<?php

namespace App\Classes\OutSourceImporter;

use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OutSourceImporterFeatureWP extends OutSourceImporterFeatureAbstract { 
    use ImporterAndSyncTrait;
    // DATA array
    protected array $params;
    protected array $tags;
    protected string $mediaGeom;

    /**
     * It imports each track of the given list to the out_source_features table.
     * 
     *
     * @return int The ID of OutSourceFeature created 
     */
    public function importTrack(){
        
        // Curl request to get the feature information from external source
        $url = $this->endpoint.'/wp-json/wp/v2/track/'.$this->source_id;
        $track = $this->curlRequest($url);

        // prepare feature parameters to pass to updateOrCreate function
        $this->params['geometry'] = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('".json_encode(unserialize($track['n7webmap_geojson']))."')) As wkt")[0]->wkt;
        $this->mediaGeom = DB::select("SELECT ST_AsText(ST_StartPoint(ST_GeomFromGeoJSON('".json_encode(unserialize($track['n7webmap_geojson']))."'))) As wkt")[0]->wkt;
        $this->params['provider'] = get_class($this);
        $this->params['type'] = $this->type;
        $this->params['raw_data'] = json_encode($track);

        // prepare the value of tags data
        $this->prepareTrackTagsJson($track);
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
        $url = $this->endpoint.'/wp-json/wp/v2/poi/'.$this->source_id;
        $poi = $this->curlRequest($url);
        
        
        // prepare feature parameters to pass to updateOrCreate function
        $geometry = '{"type":"Point","coordinates":['.$poi['n7webmap_coord']['lng'].','.$poi['n7webmap_coord']['lat'].']}';
        $geometry_poi = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('$geometry')) As wkt")[0]->wkt;
        $this->params['geometry'] = $geometry_poi;
        $this->mediaGeom = $geometry_poi;
        $this->params['provider'] = get_class($this);
        $this->params['type'] = $this->type;
        $this->params['raw_data'] = json_encode($poi);
        
        // prepare the value of tags data
        $this->preparePOITagsJson($poi);
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
        $this->tags['name'][explode('_',$track['wpml_current_locale'])[0]] = $track['title']['rendered'];
        $this->tags['description'][explode('_',$track['wpml_current_locale'])[0]] = $track['content']['rendered'];
        $this->tags['excerpt'][explode('_',$track['wpml_current_locale'])[0]] = $track['excerpt']['rendered'];
        if(!empty($track['wpml_translations'])) {
            foreach($track['wpml_translations'] as $lang){
                $locale = explode('_',$lang['locale']);
                $this->tags['name'][$locale[0]] = $lang['post_title'];
                // Curl request to get the feature translation from external source
                $url = $this->endpoint.'/wp-json/wp/v2/track/'.$lang['id'];
                $track_decode = $this->curlRequest($url);
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

        // Adds the EcOutSource:poi ID to EcOutSource:track's related_poi tags 
        if (isset($track['n7webmap_related_poi']) && is_array($track['n7webmap_related_poi'])) {
            $this->tags['related_poi'] = array();
            foreach($track['n7webmap_related_poi'] as $poi) {
                $OSF_poi = OutSourceFeature::where('endpoint',$this->endpoint)
                            ->where('source_id',$poi['ID'])
                            ->first();
                if ($OSF_poi && !is_null($OSF_poi)) {
                    array_push($this->tags['related_poi'],$OSF_poi->id);
                }
            }
        }

        // Processing the feature image of Track
        if (isset($track['featured_media']) && $track['featured_media']) {
            $url = $this->endpoint.'/wp-json/wp/v2/media/'.$track['featured_media'];
            $media = $this->curlRequest($url);
            if ($media) {}
            $this->tags['feature_image'] = $this->createOSFMediaFromWP($media);
        }

        // Processing the image Gallery of Track
        if (isset($track['n7webmap_track_media_gallery']) && $track['n7webmap_track_media_gallery']) {
            if (is_array($track['n7webmap_track_media_gallery'])){
                foreach($track['n7webmap_track_media_gallery'] as $img) {
                    $url = $this->endpoint.'/wp-json/wp/v2/media/'.$img['id'];
                    $media = $this->curlRequest($url);
                    $this->tags['image_gallery'][] = $this->createOSFMediaFromWP($media);
                }
            }
        }

        // Processing the activity
        $path = parse_url($this->endpoint);
        $file_name = str_replace('.','-',$path['host']);
        if (Storage::disk('mapping')->exists($file_name.'.json')) {
            $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');

            if (!empty(json_decode($taxonomy_map,true)['activity']) && $track['activity']) {
                foreach ($track['activity'] as $tax) {
                    $this->tags['activity'][] = json_decode($taxonomy_map,true)['activity'][$tax]['geohub_identifier'];
                }
            }
        }
    }
    
    /**
     * It populates the tags variable with the POI curl information so that it can be syncronized with EcPOI 
     * 
     * @param array $poi The OutSourceFeature parameters to be added or updated 
     * 
     */
    protected function preparePOITagsJson($poi){
        $this->tags['name'][explode('_',$poi['wpml_current_locale'])[0]] = $poi['title']['rendered'];
        $this->tags['description'][explode('_',$poi['wpml_current_locale'])[0]] = $poi['content']['rendered'];
        $this->tags['excerpt'][explode('_',$poi['wpml_current_locale'])[0]] = $poi['excerpt']['rendered'];
        if(!empty($poi['wpml_translations'])) {
            foreach($poi['wpml_translations'] as $lang){
                $locale = explode('_',$lang['locale']);
                $this->tags['name'][$locale[0]] = $lang['post_title']; 
                // Curl request to get the feature translation from external source
                $url = $this->endpoint.'/wp-json/wp/v2/poi/'.$lang['id'];
                $poi_decode = $this->curlRequest($url);
                $this->tags['description'][$locale[0]] = $poi_decode['content']['rendered'];
                $this->tags['excerpt'][$locale[0]] = $poi_decode['excerpt']['rendered'];
            }
        }
        // Adding POI parameters of accessibility
        if (isset($poi['accessibility_validity_date']))
            $this->tags['accessibility_validity_date'] = $poi['accessibility_validity_date'];
        if (isset($poi['accessibility_pdf']))
            $this->tags['accessibility_pdf'] = $poi['accessibility_pdf'];
        if (isset($poi['access_mobility_check']))
            $this->tags['access_mobility_check'] = $poi['access_mobility_check'];
        if (isset($poi['access_mobility_level']))
            $this->tags['access_mobility_level'] = $poi['access_mobility_level'];
        if (isset($poi['access_mobility_description']))
            $this->tags['access_mobility_description'] = $poi['access_mobility_description'];
        if (isset($poi['access_hearing_check']))
            $this->tags['access_hearing_check'] = $poi['access_hearing_check'];
        if (isset($poi['access_hearing_level']))
            $this->tags['access_hearing_level'] = $poi['access_hearing_level'];
        if (isset($poi['access_hearing_description']))
            $this->tags['access_hearing_description'] = $poi['access_hearing_description'];
        if (isset($poi['access_vision_check']))
            $this->tags['access_vision_check'] = $poi['access_vision_check'];
        if (isset($poi['access_vision_level']))
            $this->tags['access_vision_level'] = $poi['access_vision_level'];
        if (isset($poi['access_vision_description']))
            $this->tags['access_vision_description'] = $poi['access_vision_description'];
        if (isset($poi['access_cognitive_check']))
            $this->tags['access_cognitive_check'] = $poi['access_cognitive_check'];
        if (isset($poi['access_cognitive_level']))
            $this->tags['access_cognitive_level'] = $poi['access_cognitive_level'];
        if (isset($poi['access_cognitive_description']))
            $this->tags['access_cognitive_description'] = $poi['access_cognitive_description'];
        if (isset($poi['access_food_check']))
            $this->tags['access_food_check'] = $poi['access_food_check'];
        if (isset($poi['access_food_description']))
            $this->tags['access_food_description'] = $poi['access_food_description'];
            
        // Adding POI parameters of reachability
        if (isset($poi['reachability_by_bike_check']))
            $this->tags['reachability_by_bike_check'] = $poi['reachability_by_bike_check'];
        if (isset($poi['reachability_by_bike_description']))
            $this->tags['reachability_by_bike_description'] = $poi['reachability_by_bike_description'];
        if (isset($poi['reachability_on_foot_check']))
            $this->tags['reachability_on_foot_check'] = $poi['reachability_on_foot_check'];
        if (isset($poi['reachability_on_foot_description']))
            $this->tags['reachability_on_foot_description'] = $poi['reachability_on_foot_description'];
        if (isset($poi['reachability_by_car_check']))
            $this->tags['reachability_by_car_check'] = $poi['reachability_by_car_check'];
        if (isset($poi['reachability_by_car_description']))
            $this->tags['reachability_by_car_description'] = $poi['reachability_by_car_description'];
        if (isset($poi['reachability_by_public_transportation_check']))
            $this->tags['reachability_by_public_transportation_check'] = $poi['reachability_by_public_transportation_check'];
        if (isset($poi['reachability_by_public_transportation_description']))
            $this->tags['reachability_by_public_transportation_description'] = $poi['reachability_by_public_transportation_description'];

        // Adding POI parameters of general info
        if (isset($poi['addr:street']))
            $this->tags['addr_street'] = $poi['addr:street'];
        if (isset($poi['addr:housenumber']))
            $this->tags['addr_housenumber'] = $poi['addr:housenumber'];
        if (isset($poi['addr:postcode']))
            $this->tags['addr_postcode'] = $poi['addr:postcode'];
        if (isset($poi['addr:city']))
            $this->tags['addr_city'] = $poi['addr:city'];
        if (isset($poi['contact:phone']))
            $this->tags['contact_phone'] = $poi['contact:phone'];
        if (isset($poi['contact:email']))
            $this->tags['contact_email'] = $poi['contact:email'];
        if (isset($poi['opening_hours']))
            $this->tags['opening_hours'] = $poi['opening_hours'];
        if (isset($poi['capacity']))
            $this->tags['capacity'] = $poi['capacity'];
        if (isset($poi['stars']))
            $this->tags['stars'] = $poi['stars'];
        if (isset($poi['n7webmap_rpt_related_url']))
            $this->tags['related_url'] = $poi['n7webmap_rpt_related_url'];
        if (isset($poi['ele']))
            $this->tags['ele'] = $poi['ele'];
        if (isset($poi['code']))
            $this->tags['code'] = $poi['code'];
            
        // Adding POI parameters of style
        if (isset($poi['color']))
            $this->tags['color'] = $poi['color'];
        if (isset($poi['icon']))
            $this->tags['icon'] = $poi['icon'];
        if (isset($poi['noDetails']))
            $this->tags['noDetails'] = $poi['noDetails'];
        if (isset($poi['noInteraction']))
            $this->tags['noInteraction'] = $poi['noInteraction'];
        if (isset($poi['zindex']))
            $this->tags['zindex'] = $poi['zindex'];

        // Processing the feature image of POI
        if (isset($poi['featured_media']) && $poi['featured_media']) {
            $url = $this->endpoint.'/wp-json/wp/v2/media/'.$poi['featured_media'];
            $media = $this->curlRequest($url);
            $this->tags['feature_image'] = $this->createOSFMediaFromWP($media);
        }
        // Processing the image Gallery of POI
        if (isset($poi['n7webmap_media_gallery']) && $poi['n7webmap_media_gallery']) {
            if (is_array($poi['n7webmap_media_gallery'])){
                foreach($poi['n7webmap_media_gallery'] as $img) {
                    $url = $this->endpoint.'/wp-json/wp/v2/media/'.$img['id'];
                    $media = $this->curlRequest($url);
                    $this->tags['image_gallery'][] = $this->createOSFMediaFromWP($media);
                }
            }
        }

        // Processing the poi_type
        $path = parse_url($this->endpoint);
        $file_name = str_replace('.','-',$path['host']);
        if (Storage::disk('mapping')->exists($file_name.'.json')) {
            $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');

            if (!empty(json_decode($taxonomy_map,true)['poi_type']) && $poi['webmapp_category']) {
                foreach ($poi['webmapp_category'] as $tax) {
                    $this->tags['poi_type'][] = json_decode($taxonomy_map,true)['poi_type'][$tax]['geohub_identifier'];
                }
            }
        }
    }

    /**
     * It populates the tags variable of media so that it can be syncronized with EcMedia
     * 
     * @param array $media The OutSourceFeature parameters to be added or updated 
     * 
     */
    public function prepareMediaTagsJson($media){ 
        $tags = [];
        if(!empty($media['wpml_current_locale'])) { 
            $local_lang = explode('_',$media['wpml_current_locale'])[0];
        } else {
            $local_lang = 'it';
        }
        $tags['name'][$local_lang] = $media['title']['rendered'];
        $tags['description'][$local_lang] = $media['caption']['rendered'];
        if(!empty($media['wpml_translations'])) {
            foreach($media['wpml_translations'] as $lang){
                $locale = explode('_',$lang['locale']);
                $tags['name'][$locale[0]] = $lang['post_title']; 
                // Curl request to get the feature translation from external source
                $url = $this->endpoint.'/wp-json/wp/v2/media/'.$lang['id'];
                $media_decode = $this->curlRequest($url);
                $tags['description'][$locale[0]] = $media_decode['caption']['rendered'];
            }
        }

        try{
            // Saving the Media in to the s3-osfmedia storage
            $wp_url = $media['guid']['rendered'];
            $url_encoded = preg_replace_callback('/[^\x20-\x7f]/', function($match) {
                return urlencode($match[0]);
            }, $wp_url);
            $contents = file_get_contents($url_encoded);
            $basename = explode('.',basename($wp_url));
            // TODO: Change importer-osfmedia to s3-osfmedia storage
            $s3_osfmedia = Storage::disk('importer-osfmedia');
            $s3_osfmedia->put(sha1($basename[0]) . '.' . $basename[1], $contents);

            $tags['url'] = sha1($basename[0]) . '.' . $basename[1];
        } catch(Exception $e) {
            echo $e;
        }

        return $tags;
    }
}