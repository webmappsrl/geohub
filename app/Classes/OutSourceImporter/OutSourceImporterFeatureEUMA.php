<?php

namespace App\Classes\OutSourceImporter;

use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OutSourceImporterFeatureEUMA extends OutSourceImporterFeatureAbstract { 
    use ImporterAndSyncTrait;
    // DATA array
    protected array $params;
    protected array $tags;
    protected string $mediaGeom;
    protected string $poi_type;

    /**
     * It imports each track of the given list to the out_source_features table.
     * 
     *
     * @return int The ID of OutSourceFeature created 
     */
    public function importTrack(){
        $error_not_created = [];
        try {
            // Curl request to get the feature information from external source
            $url = 'https://prod.eumadb.webmapp.it/api/v1/trail/geojson/'.$this->source_id;
            $track = $this->curlRequest($url);
    
            // prepare feature parameters to pass to updateOrCreate function
            Log::info('Preparing OSF Track with external ID: '.$this->source_id);
            $this->params['geometry'] = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('".json_encode($track['geometry'])."')) As wkt")[0]->wkt;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            // $this->params['raw_data'] = json_encode($track);
    
            // prepare the value of tags data
            Log::info('Preparing OSF Track TAGS with external ID: '.$this->source_id);
            $this->prepareTrackTagsJson($track);
            $this->params['tags'] = $this->tags;
            Log::info('Finished preparing OSF Track with external ID: '.$this->source_id);
            Log::info('Starting creating OSF Track with external ID: '.$this->source_id);
            return $this->create_or_update_feature($this->params);
        } catch (Exception $e) {
            array_push($error_not_created,$url);
            Log::info('Error creating OSF from external with id: '.$this->source_id."\n ERROR: ".$e->getMessage());
        }
        if ($error_not_created) {
            Log::info('Ec features not created from Source with URL: ');
            foreach ($error_not_created as $url) {
                Log::info($url);
            }
        }
    }

    /**
     * It imports each POI of the given list to the out_source_features table.
     * 
     *
     * @return int The ID of OutSourceFeature created 
     */
    public function importPoi(){
        // Curl request to get the feature information from external source
        if (strpos($this->endpoint,'hut')) {
            $url = 'https://prod.eumadb.webmapp.it/api/v1/hut/geojson/'.$this->source_id;
            $this->poi_type = 'alpine-hut';
        }
        if (strpos($this->endpoint,'climbingrockarea')) {
            $url = 'https://prod.eumadb.webmapp.it/api/v1/climbingrockarea/geojson/'.$this->source_id;
            $this->poi_type = 'climbing-crag';

        }
        $poi = $this->curlRequest($url);
        
        
        // prepare feature parameters to pass to updateOrCreate function
        Log::info('Preparing OSF POI with external ID: '.$this->source_id);
        try{
            $geometry_poi = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('".json_encode($poi['geometry'])."')) As wkt")[0]->wkt;
            $this->params['geometry'] = $geometry_poi;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['raw_data'] = json_encode($poi);
            
            // prepare the value of tags data
            Log::info('Preparing OSF POI TAGS with external ID: '.$this->source_id);
            $this->tags = [];
            $this->preparePOITagsJson($poi);
            $this->params['tags'] = $this->tags;
            Log::info('Finished preparing OSF POI with external ID: '.$this->source_id);
            Log::info('Starting creating OSF POI with external ID: '.$this->source_id);
            return $this->create_or_update_feature($this->params);
        } catch (Exception $e) {
            Log::info('Error creating OSF : '.$e);
        }
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
        Log::info('Preparing OSF Track TRANSLATIONS with external ID: '.$this->source_id);
        if (isset($track['properties']['name'])){
            $trackname = html_entity_decode($track['properties']['name']);
        } else {
            $trackname = $track['properties']['ref'] . ' - ' . $track['properties']['member_acronym'];
        }
        $this->tags['name']['it'] = $trackname;
        if (isset($track['properties']['url'])) {
            $urlarray = explode(',',$track['properties']['url']);
            foreach($urlarray as $url) {
                $related_url_name = parse_url($url);
                if (isset($related_url_name['host'])) {
                    $this->tags['related_url'][$related_url_name['host']] = $url;
                } else {
                    $this->tags['related_url'][$related_url_name['path']] = $url;
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
        Log::info('Preparing OSF POI TRANSLATIONS with external ID: '.$this->source_id);
        if (isset($poi['properties']['official_name'])){
            $poiname = html_entity_decode($poi['properties']['official_name']);
        } elseif (isset($poi['properties']['second_official_name'])){
            $poiname = $poi['properties']['second_official_name'];
        } elseif (isset($poi['properties']['name'])){
            $poiname = html_entity_decode($poi['properties']['name']);
        } elseif (isset($poi['properties']['original_name'])){
            $poiname = html_entity_decode($poi['properties']['original_name']);
        } elseif (isset($poi['properties']['english_name'])){
            $poiname = html_entity_decode($poi['properties']['english_name']);
        } else {
            $poiname = $poi['properties']['alternative_name'];
        }
        
        $this->tags['name']['it'] = $poiname;

        // Adding ACF of Itinera Romanica to description
        if (isset($poi['properties']['description'])) {
            $this->tags['description']['it'] = $poi['properties']['description'];
        }

        // Adding POI parameters of general info
        Log::info('Preparing OSF POI GENERAL INFO with external ID: '.$this->source_id);
        if (isset($poi['properties']['elevation']))
            $this->tags['ele'] = $poi['properties']['elevation'];
        if (isset($poi['properties']['address']))
            $this->tags['addr_complete'] = $poi['properties']['address'];
        if (isset($poi['properties']['operating_phone']))
            $this->tags['contact_phone'] = $poi['properties']['operating_phone'];
        if (isset($poi['properties']['operating_email']))
            $this->tags['contact_email'] = $poi['properties']['operating_email'];
        if (isset($poi['properties']['url'])) {
                $urlarray = explode(',',$poi['properties']['url']);
                foreach($urlarray as $url) {
                    $related_url_name = parse_url($url);
                    if (isset($related_url_name['host'])) {
                        $this->tags['related_url'][$related_url_name['host']] = $url;
                    } else {
                        $this->tags['related_url'][$related_url_name['path']] = $url;
                    }
                }
        }
        

        // Processing the poi_type
        Log::info('Preparing OSF POI POI_TYPE MAPPING with external ID: '.$this->source_id);
        $this->tags['poi_type'][] = $this->poi_type;
    }
}