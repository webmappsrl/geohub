<?php

namespace App\Classes\OutSourceImporter;

use App\Helpers\OutSourceImporterHelper;
use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Traits\ImporterAndSyncTrait;

class OutSourceImporterFeatureStorageCSV extends OutSourceImporterFeatureAbstract { 
    use ImporterAndSyncTrait;

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
        return 'getMediaList result';
    }

    /**
     * It imports each POI of the given list to the out_source_features table.
     * 
     *
     * @return int The ID of OutSourceFeature created 
     */
    public function importPoi(){
        $path = $this->CreateStoragePathFromEndpoint($this->endpoint);
        $file = fopen($path, "r");
        $header = NULL;
        $poi = array();
        while ( ($row = fgetcsv($file, 1000, ",")) !==FALSE )
        {
            if (!$header)
                $header = $row;
            
            $id = $row[0];

            if ($id == $this->source_id) {
                $poi = array_combine($header, $row);
            }
        }

        fclose($file);
        
        // prepare the value of tags data
        $this->preparePOITagsJson($poi);
        $geometry = '{"type":"Point","coordinates":['.$poi['lon'].','.$poi['lat'].']}';
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
        
    }
    
    /**
     * It populates the tags variable with the POI curl information so that it can be syncronized with EcPOI 
     * 
     * @param array $poi The OutSourceFeature parameters to be added or updated 
     * 
     */
    protected function preparePOITagsJson($poi){
        $this->tags['name']['it'] = $poi['name'];

        // Adding POI parameters of general info
        if (isset($poi['address_complete']))
            $this->tags['address_complete'] = $poi['address_complete'];
        if (isset($poi['contact_phone']))
            $this->tags['contact_phone'] = $poi['contact_phone'];
        if (isset($poi['contact_email']))
            $this->tags['contact_email'] = $poi['contact_email'];
        if (isset($poi['capacity']))
            $this->tags['capacity'] = $poi['capacity'];
        if (isset($poi['stars']))
            $this->tags['stars'] = $poi['stars'];
        if (isset($poi['related_url']))
            $this->tags['related_url'] = $poi['related_url'];
        if (isset($poi['code']))
            $this->tags['code'] = $poi['code'];

    }
}