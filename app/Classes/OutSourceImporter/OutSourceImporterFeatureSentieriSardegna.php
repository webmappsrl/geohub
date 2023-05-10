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
            $this->mediaGeom = DB::select("SELECT ST_AsText(ST_StartPoint(ST_LineMerge(ST_GeomFromGeoJSON('".json_encode($track['geometry'])."')))) As wkt")[0]->wkt;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['endpoint_slug'] = 'sardegna-sentieri-poi';
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
            $this->mediaGeom = $geometry_poi;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['endpoint_slug'] = 'sardegna-sentieri-poi';
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
            $this->tags['description'] = $track['properties']['description'];
        }

        if (isset($track['properties']['codice_cai'])) {
            $this->tags['ref'] = $track['properties']['codice_cai'];
        }

        // Related Poi of vicinity
        if ($this->params['geometry']) {
            $geometry = $this->params['geometry'];
            $related_pois = DB::select("SELECT id from out_source_features WHERE type='poi' and endpoint='https://sentieri.netseven.work/ss/listpoi/?_format=json' and ST_Contains(ST_BUFFER(ST_SetSRID(ST_GeomFromText('$geometry'),4326),0.01, 'endcap=round join=round'),geometry::geometry);");
            
            if (is_array($related_pois) && !empty($related_pois)) {
                foreach ($related_pois as $poi) {
                    $this->tags['related_poi'][] = $poi->id;
                }
            }
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
                        if (is_array($idList)) {
                            foreach ($idList as $id) {
                                $this->tags['theme'][] = json_decode($taxonomy_map,true)['theme'][$id]['geohub_identifier'];
                            }
                        } else {
                            $this->tags['theme'][] = json_decode($taxonomy_map,true)['theme'][$idList]['geohub_identifier'];
                        }
                    }
                }
            }
        }

        // Processing the feature image of Track
        if (isset($track['properties']['immagine_principale'])) {
            Log::info('Preparing OSF Track FEATURE_IMAGE with external ID: '.$this->source_id);
            if ($track['properties']['immagine_principale']) {
                $this->tags['feature_image'] = $this->createOSFMediaFromLink($track['properties']['immagine_principale']);
            } else {
                Log::info('ERROR reaching media: '.$track['properties']['immagine_principale']);
            }
        }

        // Processing the image Gallery of Track
        if (isset($track['properties']['galleria_immagini'])) {
            if (is_array($track['properties']['galleria_immagini'])){
                Log::info('Preparing OSF Track IMAGE_GALLERY with external ID: '.$this->source_id);
                foreach($track['properties']['galleria_immagini'] as $img) {
                    if ($img) {
                        $this->tags['image_gallery'][] = $this->createOSFMediaFromLink($img);
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
            $this->tags['description'] = $poi['properties']['description'];
        } 

        if (isset($poi['properties']['codice']))
            $this->tags['code'] = $poi['properties']['codice'];

        if (isset($poi['properties']['addr_locality']))
            $this->tags['addr_complete'] = $poi['properties']['addr_locality'];

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
                            Log::info('tax created : '.$id);
                            $this->tags['poi_type'][] = json_decode($taxonomy_map,true)['poi_type'][$id]['geohub_identifier'];
                        }
                    }
                }
            }
        }


        // Processing the feature image of POI
        if (isset($poi['properties']['immagine_principale'])) {
            Log::info('Preparing OSF POI FEATURE_IMAGE with external ID: '.$this->source_id);
            
            if ($poi['properties']['immagine_principale']) {
                $this->tags['feature_image'] = $this->createOSFMediaFromLink($poi['properties']['immagine_principale']);
            } else {
                Log::info('ERROR reaching media: '.$poi['properties']['immagine_principale']);
            }
        }
    }

    protected function createOSFMediaFromLink($image_url) {
        $tags = [];
        try{
            // Saving the Media in to the s3-osfmedia storage (.env in production)
            $storage_name = config('geohub.osf_media_storage_name');
            Log::info('Geting image from url: '.$image_url);
            $url_encoded = preg_replace_callback('/[^\x20-\x7f]/', function($match) {
                return urlencode($match[0]);
            }, $image_url);
            $contents = Http::withBasicAuth('sentieri','bai1Eevuvah7')->get($url_encoded);
            $basename = explode('.',basename($image_url));
            $s3_osfmedia = Storage::disk($storage_name);
            $osf_name_tmp = sha1($basename[0]) . '.' . $basename[1];
            $s3_osfmedia->put($osf_name_tmp, $contents->body());

            Log::info('Saved OSF Media with name: '.$osf_name_tmp);
            $tags['url'] = ($s3_osfmedia->exists($osf_name_tmp))?$osf_name_tmp:'';
            $tags['name']['it'] = $basename[0];
        } catch(Exception $e) {
            echo $e;
            Log::info('Saving media in s3-osfmedia error:' . $e);
        }

        $media_id = random_int(900000000, 999999999);
        $media_id = $this->source_id.$media_id;
        Log::info('Preparing OSF MEDIA TAGS with external ID: '.$media_id);
        $params['tags'] = $tags;
        $params['type'] = 'media';
        $params['provider'] = get_class($this);
        $params['geometry'] = $this->mediaGeom;
        Log::info('Starting creating OSF MEDIA with external ID: '.$media_id);
        $feature = OutSourceFeature::updateOrCreate(
            [
                'source_id' => $media_id,
                'endpoint' => $this->endpoint
            ],$params);
        return $feature->id;
    }
}