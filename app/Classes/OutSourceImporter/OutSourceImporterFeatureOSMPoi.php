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
}