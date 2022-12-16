<?php

namespace App\Classes\OutSourceImporter;

use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OutSourceImporterFeatureOSMPoi extends OutSourceImporterFeatureAbstract { 
    use ImporterAndSyncTrait;
    // DATA array
    protected array $params;
    protected array $tags;
    protected string $mediaGeom;
    protected string $poi_type;

    /**
     * TODO: It imports each track of the given list to the out_source_features table.
     * 
     *
     * @return int The ID of OutSourceFeature created 
     */
    public function importTrack() {}
    

    /**
     * It imports each POI of the given list to the out_source_features table.
     * $OSF = new App\Classes\OutSourceImporter\OutSourceImporterFeatureOSMPoi('poi','osmpoi:caiparma_bivacchi','node/388397829');
     * $OSF = new App\Classes\OutSourceImporter\OutSourceImporterFeatureOSMPoi('poi','osmpoi:caiparma_bivacchi','way/145096288');
     * $OSF_id = $OSF->importFeature();
     * 
     * ABSTRACT construct:
     * public function __construct(string $type, string $endpoint, string $source_id, bool $only_related_url = false) 
     * 
     *
     * @return int The ID of OutSourceFeature created 
     */
    public function importPoi(){
        // TODO: need to manage poi_type ?
        $this->poi_type = '';
        $poi = $this->getGeojsonFromOsm($this->source_id);
                
        // prepare feature parameters to pass to updateOrCreate function
        Log::info('Preparing OSF POI with external ID: '.$this->source_id);
        try{
            $geometry_poi = DB::select("SELECT ST_AsText(ST_Centroid(ST_GeomFromGeoJSON('".json_encode($poi['geometry'])."'))) As wkt")[0]->wkt;
            $this->params['geometry'] = $geometry_poi;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['raw_data'] = json_encode($poi);
            
            // prepare the value of tags data
            Log::info('Preparing OSF POI TAGS with external ID: '.$this->source_id);
            $this->tags = [];
            $this->params['tags'] = $this->prepareTagsForPoiWithOsmMapping($poi);
            Log::info('Finished preparing OSF POI with external ID: '.$this->source_id);
            Log::info('Starting creating OSF POI with external ID: '.$this->source_id);
            return $this->create_or_update_feature($this->params);
        } catch (Exception $e) {
            Log::info('Error creating OSF : '.$e);
        }
    }

    /**
     * Not needed
     *
     * @return void
     */
    public function importMedia(){
        return 'Not needed';
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
     * TODO: It populates the tags variable with the POI curl information so that it can be syncronized with EcPOI 
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