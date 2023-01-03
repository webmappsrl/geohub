<?php

namespace App\Classes\OutSourceImporter;

use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use App\Providers\OsmServiceProvider;
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
        $osmp = app(OsmServiceProvider::class);
        $poi = json_decode($osmp->getGeojson($this->source_id),true);
                
        // prepare feature parameters to pass to updateOrCreate function
        Log::info('Preparing OSF POI with external ID: '.$this->source_id);
        try{
            $geometry_poi = DB::select("SELECT ST_AsText(ST_Centroid(ST_GeomFromGeoJSON('".json_encode($poi['geometry'])."'))) As wkt")[0]->wkt;
            $this->params['geometry'] = $geometry_poi;
            $this->mediaGeom = $geometry_poi;
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
     * TODO: implement
     *
     * @return void
     */
    public function importMedia(){
        return 'Not implemented';
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
     * It populates the tags variable of media so that it can be syncronized with EcMedia
     * 
     * @param array $media The OutSourceFeature parameters to be added or updated 
     * 
     */
    public function prepareMediaTagsJson($media){ 
        foreach($media['query']['pages'] as $pageid => $array) {
            $media_id = $pageid;
            $media = $array;
            break;
        }
        Log::info('Preparing OSF MEDIA TRANSLATIONS with external ID: '.$media_id);
        $tags = [];
        $tags['name']['it'] = $media['title'];

        try{
            // Saving the Media in to the s3-osfmedia storage (.env in production)
            $storage_name = config('geohub.osf_media_storage_name');
            Log::info('Saving OSF MEDIA on storage '.$storage_name);
            Log::info(" ");
            if (isset($media['imageinfo']) && isset($media['imageinfo'][0])) {
                $url_encoded = $media['imageinfo'][0]['url'];
            } 
            $options  = array('http' => array('user_agent' => 'custom user agent string'));
            $context  = stream_context_create($options);
            $contents = file_get_contents($url_encoded, false, $context);
            $basename = explode('.',basename($url_encoded));
            $s3_osfmedia = Storage::disk($storage_name);
            $osf_name_tmp = sha1($basename[0]) . '.' . $basename[1];
            $s3_osfmedia->put($osf_name_tmp, $contents);

            Log::info('Saved OSF Media with name: '.$osf_name_tmp);
            $tags['url'] = ($s3_osfmedia->exists($osf_name_tmp))?$osf_name_tmp:'';
        } catch(Exception $e) {
            echo $e;
            Log::info('Saving media in s3-osfmedia error:' . $e);
        }

        return $tags;
    }
}