<?php

namespace App\Classes\OutSourceImporter;

use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OutSourceImporterFeatureOSM2CAI extends OutSourceImporterFeatureAbstract { 
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
        $db = DB::connection('out_source_osm');
        $track = $db->table('hiking_routes')
            ->where('id',$this->source_id)
            ->select([
                'ref',
                'name',
                'cai_scale',
                'from',
                'to',
                'geometry',
                'duration_forward',
                'description',
                'note',
                'website',
                'distance',
            ])
            ->first();

        // prepare feature parameters to pass to updateOrCreate function
        Log::info('Preparing OSF Track with external ID: '.$this->source_id);
        $this->params['geometry'] = DB::select("SELECT ST_AsText(ST_LineMerge('$track->geometry'))")[0]->st_astext;
        $this->params['provider'] = get_class($this);
        $this->params['type'] = $this->type;
        $this->params['raw_data'] = json_encode($track);

        // prepare the value of tags data
        Log::info('Preparing OSF Track TAGS with external ID: '.$this->source_id);
        $this->prepareTrackTagsJson($track);
        $this->params['tags'] = $this->tags;
        Log::info('Finished preparing OSF Track with external ID: '.$this->source_id);
        Log::info('Starting creating OSF Track with external ID: '.$this->source_id);
        return $this->create_or_update_feature($this->params);
    }

    /**
     * It imports each POI of the given list to the out_source_features table.
     * 
     *
     * @return int The ID of OutSourceFeature created 
     */
    public function importPoi(){
        return 'getPoiList result';
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
     * @param object $track The OutSourceFeature parameters to be added or updated 
     * 
     */
    protected function prepareTrackTagsJson($track){
        Log::info('Preparing OSF Track TRANSLATIONS with external ID: '.$this->source_id);
        $this->tags['name']['it'] = $track->name;
        $this->tags['description']['it'] = $track->description;
        $this->tags['excerpt']['it'] = $track->note;
        $this->tags['from'] = $track->from;
        $this->tags['to'] = $track->to;
        $this->tags['difficulty'] = $track->cai_scale;
        $this->tags['related_url'] = $track->website;
        $this->tags['ref'] = $track->ref;

    }
    
    /**
     * It populates the tags variable with the POI curl information so that it can be syncronized with EcPOI 
     * 
     * @param array $poi The OutSourceFeature parameters to be added or updated 
     * 
     */
    protected function preparePOITagsJson($poi){
        return [];
    }

    /**
     * It populates the tags variable of media so that it can be syncronized with EcMedia
     * 
     * @param array $media The OutSourceFeature parameters to be added or updated 
     * 
     */
    public function prepareMediaTagsJson($media){ 
        return [];
    }
}