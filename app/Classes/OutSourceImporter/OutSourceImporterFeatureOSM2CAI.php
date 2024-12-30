<?php

namespace App\Classes\OutSourceImporter;

use App\Models\OutSourceFeature;
use App\Providers\CurlServiceProvider;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use stdClass;

class OutSourceImporterFeatureOSM2CAI extends OutSourceImporterFeatureAbstract
{
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
    public function importTrack()
    {

        // Curl request to get the feature information from external source
        $db = DB::connection('out_source_osm');
        $track = $db->table('hiking_routes')
            ->where('id', $this->source_id)
            ->select([
                'id',
                //'ref',
                //'name',
                //'cai_scale',
                //'from',
                //'to',
                'osmfeatures_data',
                //'duration_forward',
                //'description',
                //'note',
                //'website',
                //'distance',
                'osm2cai_status',
                'issues_status',
                'issues_description',
                'issues_last_update'
            ])
            ->selectRaw("ST_AsText(ST_Force2D(ST_LineMerge(geometry::geometry))) as geometry")
            ->first();

        if (!$track) {
            Log::error('Unable to find an hiking route on connection out_source_osm with ID: ' . $this->source_id);
            return 0;
        }


        $osmData = json_decode($track->osmfeatures_data, true)['properties'] ?? [];
        unset($track->osmfeatures_data);
        foreach ($osmData as $key => $value) {
            $track->$key = $value;
        }

        // dd($track);

        // prepare feature parameters to pass to updateOrCreate function
        Log::info('Preparing OSF Track with external ID: ' . $this->source_id);
        $this->params['geometry'] = $track->geometry;
        $this->params['provider'] = get_class($this);
        $this->params['type'] = $this->type;
        $trackJson = json_encode($track);
        $this->params['raw_data'] = $trackJson;
        $this->params['properties'] = $trackJson;

        // prepare the value of tags data
        Log::info('Preparing OSF Track TAGS with external ID: ' . $this->source_id);
        $this->prepareTrackTagsJson($track);
        $this->params['tags'] = $this->tags;
        Log::info('Finished preparing OSF Track with external ID: ' . $this->source_id);
        Log::info('Starting creating OSF Track with external ID: ' . $this->source_id);
        return $this->create_or_update_feature($this->params);
    }

    /**
     * It imports each POI of the given list to the out_source_features table.
     *
     *
     * @return int The ID of OutSourceFeature created
     */
    public function importPoi()
    {
        return 'getPoiList result';
    }

    public function importMedia()
    {
        return 'getMediaList result';
    }

    /**
     * It updateOrCreate method of the class OutSourceFeature
     *
     * @param array $params The OutSourceFeature parameters to be added or updated
     * @return int The ID of OutSourceFeature created
     */
    protected function create_or_update_feature(array $params)
    {

        $feature = OutSourceFeature::updateOrCreate(
            [
                'source_id' => $this->source_id,
                'endpoint' => $this->endpoint
            ],
            $params
        );
        return $feature->id;
    }

    /**
     * It populates the tags variable with the track curl information so that it can be syncronized with EcTrack
     *
     * @param object $track The OutSourceFeature parameters to be added or updated
     *
     */
    protected function prepareTrackTagsJson($track)
    {
        Log::info('Preparing OSF Track TRANSLATIONS with external ID: ' . $this->source_id);
        if (isset($track->name)) {
            $this->tags['name']['it'] = $track->name;
        }
        $this->tags['description']['it'] = '';
        if (isset($track->description)) {
            $this->tags['description']['it'] = $track->description . '<br>';
        }
        if (isset($track->osm2cai_status)) {
            $this->tags['sda'] = $track->osm2cai_status;
            $this->tags['description']['it'] .= 'Stato di accatastamento: <strong>' . $track->osm2cai_status . '</strong> (' . $this->getSDADescription($track->osm2cai_status)  . ')<br>';
        }
        $this->tags['description']['it'] .= "<a href='https://osm2cai.cai.it/resources/hiking-routes/$track->id' target='_blank'>Modifica questo percorso</a>";
        if (isset($track->note)) {
            $this->tags['excerpt']['it'] = $track->note;
        }
        if (isset($track->from)) {
            $this->tags['from'] = $track->from;
        }
        if (isset($track->to)) {
            $this->tags['to'] = $track->to;
        }
        if (isset($track->cai_scale)) {
            $this->tags['cai_scale'] = $track->cai_scale;
        }
        if (isset($track->website) && $track->website) {
            $related_url_name = parse_url($track->website);
            $host = $track->website;
            if (isset($related_url_name['host']) && $related_url_name['host']) {
                $host = $related_url_name['host'];
            }
            $this->tags['related_url'][$host] = $track->website;
        }
        if (isset($track->ref)) {
            $this->tags['ref'] = $track->ref;
        }
    }

    /**
     * It populates the tags variable with the POI curl information so that it can be syncronized with EcPOI
     *
     * @param array $poi The OutSourceFeature parameters to be added or updated
     *
     */
    protected function preparePOITagsJson($poi)
    {
        return [];
    }

    /**
     * It populates the tags variable of media so that it can be syncronized with EcMedia
     *
     * @param array $media The OutSourceFeature parameters to be added or updated
     *
     */
    public function prepareMediaTagsJson($media)
    {
        return [];
    }

    /**
     * It returns the description of the osm2cai status
     *
     * @param integer $sda track osm2cai status
     *
     */
    public function getSDADescription($sda)
    {
        $description = '';
        switch ($sda) {
            case '0':
                $description = 'Non rilevato, senza scala di difficoltà';
                break;
            case '1':
                $description = 'Percorsi non rilevati, con scala di difficoltà';
                break;
            case '2':
                $description = 'Percorsi rilevati, senza scala di difficoltá';
                break;
            case '3':
                $description = 'Percorsi rilevati, con scala di difficoltá';
                break;
            case '4':
                $description = 'Percorsi importati in INFOMONT';
                break;
        }
        return $description;
    }
}
