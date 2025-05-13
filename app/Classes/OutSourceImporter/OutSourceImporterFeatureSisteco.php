<?php

namespace App\Classes\OutSourceImporter;

use App\Models\OutSourceFeature;
use App\Traits\ImporterAndSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutSourceImporterFeatureSisteco extends OutSourceImporterFeatureAbstract
{
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
    public function importTrack() {}

    /**
     * It imports each POI of the given list to the out_source_features table.
     *
     *
     * @return int The ID of OutSourceFeature created
     */
    public function importPoi()
    {
        $url = 'https://sisteco.maphub.it/api/v1/geomtopoint/cadastralparcel/'.$this->source_id;
        $poi = $this->curlRequest($url);

        // prepare feature parameters to pass to updateOrCreate function
       $this->logChannel->info('Preparing OSF POI with external ID: ' . $this->source_id);
        try {
            $geometry_poi = DB::select("SELECT ST_AsText(ST_GeomFromGeoJSON('" . json_encode($poi['geometry']) . "')) As wkt")[0]->wkt;
            $this->params['geometry'] = $geometry_poi;
            $this->params['provider'] = get_class($this);
            $this->params['type'] = $this->type;
            $this->params['raw_data'] = json_encode($poi);

            // prepare the value of tags data
            $this->logChannel->info('Preparing OSF POI TAGS with external ID: ' . $this->source_id);
            $this->tags = [];
            $this->preparePOITagsJson($poi);
            $this->params['tags'] = $this->tags;
            $this->logChannel->info('Finished preparing OSF POI with external ID: ' . $this->source_id);
            $this->logChannel->info('Starting creating OSF POI with external ID: ' . $this->source_id);

            return $this->create_or_update_feature($this->params);
        } catch (Exception $e) {
            $this->logChannel->info('Error creating OSF : ' . $e);
        }
    }

    public function importMedia()
    {
        return 'getMediaList result';
    }

    /**
     * It updateOrCreate method of the class OutSourceFeature
     *
     * @param  array  $params  The OutSourceFeature parameters to be added or updated
     * @return int The ID of OutSourceFeature created
     */
    protected function create_or_update_feature(array $params)
    {
        try {
            $feature = OutSourceFeature::updateOrCreate(
                [
                    'source_id' => $this->source_id,
                    'endpoint' => $this->endpoint,
                ],
                $params
            );

            return $feature->id;
        } catch (Exception $e) {
            $this->logChannel->info('Error createOrUpdate OSF: ' . $e);
        }
    }

    /**
     * It populates the tags variable with the track curl information so that it can be syncronized with EcTrack
     *
     * @param  array  $track  The OutSourceFeature parameters to be added or updated
     */
    protected function prepareTrackTagsJson($track) {}

    /**
     * It populates the tags variable with the POI curl information so that it can be syncronized with EcPOI
     *
     * @param  array  $poi  The OutSourceFeature parameters to be added or updated
     */
    protected function preparePOITagsJson($poi)
    {
        if (isset($poi['name'])) {
            $this->tags['name'] = $poi['name'];
        }

        if (isset($poi['description'])) {
            $this->tags['description'] = $poi['description'];
        }

        if (isset($poi['related_url'])) {
            $urlarray = explode(',', $poi['related_url'][0]);
            foreach ($urlarray as $url) {
                $related_url_name = parse_url($url);
                if (isset($related_url_name['host'])) {
                    $this->tags['related_url'][$related_url_name['host']] = $url;
                } else {
                    $this->tags['related_url'][$related_url_name['path']] = $url;
                }
            }
        }
    }
}
