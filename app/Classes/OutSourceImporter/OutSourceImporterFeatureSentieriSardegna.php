<?php

namespace App\Classes\OutSourceImporter;

use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OutSourceImporterFeatureSentieriSardegna extends OutSourceImporterFeatureAbstract { 
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
            $url = 'https://sentieri.netseven.work/ss/sentiero/'.$this->source_id.'?_format=json';
            $response = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get($url);
            $track = $response->json();
    
            // prepare feature parameters to pass to updateOrCreate function
            Log::info('Preparing OSF Track with external ID: '.$this->source_id);
            $this->params['geometry'] = DB::select("SELECT ST_AsText(ST_LineMerge(ST_GeomFromGeoJSON('".json_encode($track['geometry'])."'))) As wkt")[0]->wkt;
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

        $url = 'https://sentieri.netseven.work/ss/poi/'.$this->source_id.'?_format=json';
        $response = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get($url);
        $poi = $response->json();
        
        
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
            $this->tags['name'] = $track['properties']['name'];
        } 
        if (isset($track['properties']['description'])){
            $this->tags['description'] = html_entity_decode($track['properties']['description']);
        }

        if (isset($track['properties']['codice_cai'])) {
            $this->tags['ref'] = $track['properties']['codice_cai'];
        }

        // Processing the theme
        if (isset($track['properties']['taxonomies'])) {
            Log::info('Preparing OSF TRACK theme MAPPING with external ID: '.$this->source_id);
            
            $path = parse_url($this->endpoint);
            $file_name = str_replace('.','-',$path['host']);
            if (Storage::disk('mapping')->exists($file_name.'.json')) {
                $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');

                if (!empty(json_decode($taxonomy_map,true)['theme'])) {
                    foreach ($track['properties']['taxonomies'] as $tax => $idList) {
                        foreach ($idList as $id) {
                            $this->tags['theme'][] = json_decode($taxonomy_map,true)['theme'][$id]['geohub_identifier'];
                        }
                    }
                }
            }
        }

        // Processing the feature image of Track
        if (isset($track['properties']['immagine_principale']) && $track['properties']['immagine_principale']) {
            Log::info('Preparing OSF Track FEATURE_IMAGE with external ID: '.$this->source_id);
            $media = Http::get($track['properties']['immagine_principale']);
            if ($media) {
                $this->tags['feature_image'] = $this->createOSFMediaFromWP($media);
            } else {
                Log::info('ERROR reaching media: '.$track['properties']['immagine_principale']);
            }
        }

        // Processing the image Gallery of Track
        if (isset($track['properties']['immagine_principale']) && $track['properties']['immagine_principale']) {
            if (is_array($track['properties']['immagine_principale'])){
                Log::info('Preparing OSF Track IMAGE_GALLERY with external ID: '.$this->source_id);
                foreach($track['properties']['immagine_principale'] as $img) {
                    $media = Http::get($img);
                    if ($media) {
                        $this->tags['image_gallery'][] = $this->createOSFMediaFromWP($media);
                    } else {
                        Log::info('ERROR reaching media: '.$img);
                    }
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
        if (isset($poi['properties']['name'])){
            $this->tags['name'] = $poi['properties']['name'];
        } 
        if (isset($poi['properties']['description'])){
            $this->tags['description'] = html_entity_decode($poi['properties']['description']);
        } 

        if (isset($poi['properties']['codice']))
            $this->tags['code'] = $poi['properties']['codice'];

        // Processing the poi_type
        if (isset($poi['properties']['taxonomies'])) {
            Log::info('Preparing OSF POI POI_TYPE MAPPING with external ID: '.$this->source_id);
            
            $path = parse_url($this->endpoint);
            $file_name = str_replace('.','-',$path['host']);
            if (Storage::disk('mapping')->exists($file_name.'.json')) {
                $taxonomy_map = Storage::disk('mapping')->get($file_name.'.json');

                if (!empty(json_decode($taxonomy_map,true)['poi_type'])) {
                    foreach ($poi['properties']['taxonomies'] as $tax => $idList) {
                        foreach ($idList as $id) {
                            $this->tags['poi_type'][] = json_decode($taxonomy_map,true)['poi_type'][$id]['geohub_identifier'];
                        }
                    }
                }
            }
        }


        // Processing the feature image of POI
        if (isset($poi['properties']['immagine_principale']) && $poi['properties']['immagine_principale']) {
            Log::info('Preparing OSF POI FEATURE_IMAGE with external ID: '.$this->source_id);
            $media = Http::get($poi['properties']['immagine_principale']);
            if ($media) {
                $this->tags['feature_image'] = $this->createOSFMediaFromWP($media);
            } else {
                Log::info('ERROR reaching media: '.$poi['properties']['immagine_principale']);
            }
        }
    }
}