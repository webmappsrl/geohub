<?php

namespace App\Traits;

use App\Models\EcTrack;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Http;

trait TrackElasticIndexTrait
{
    /**
     * Creates or Updates the EcTrack index on Elastic
     *
     * @param String $index_name
     * @param Array $layers
     * @return void
     */
    public function elasticIndexUpsert($index_name, $layers): void
    {
        Log::info('Update Elastic Indexing track ' . $this->id);

        $geom = EcTrack::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(ST_Force2D(geometry)) as geom")
            )
            ->first()
            ->geom;

        // FEATURE IMAGE
        $feature_image = '';
        if (isset($this->featureImage->thumbnails)) {
            $sizes = json_decode($this->featureImage->thumbnails, true);
            // TODO: use proper ecMedia function
            if (isset($sizes['400x200'])) {
                $feature_image = $sizes['400x200'];
            } elseif (isset($sizes['225x100'])) {
                $feature_image = $sizes['225x100'];
            }
        }

        // TODO: converti into array for ELASTIC correct datatype
        // Refers to: https://www.elastic.co/guide/en/elasticsearch/reference/current/array.html
        $taxonomy_activities = '[]';
        if ($this->taxonomyActivities->count() > 0) {
            $taxonomy_activities = $this->taxonomyActivities->pluck('identifier')->toArray();
        }
        $taxonomy_wheres = '[]';
        if ($this->taxonomyWheres->count() > 0) {
            // add tax where first show to the end of taxonomy_wheres array
            if ($this->taxonomy_wheres_show_first) {
                $taxonomy_wheres = $this->taxonomyWheres->pluck('name', 'id')->toArray();
                $first_show_name = $taxonomy_wheres[$this->taxonomy_wheres_show_first];
                unset($taxonomy_wheres[$this->taxonomy_wheres_show_first]);
                $taxonomy_wheres = array_values($taxonomy_wheres);
                array_push($taxonomy_wheres, $first_show_name);
                $taxonomy_wheres = $taxonomy_wheres;
            } else {
                $taxonomy_wheres = $this->taxonomyWheres->pluck('name')->toArray();
            }
        }

        $taxonomy_themes = '[]';
        if ($this->taxonomyThemes->count() > 0) {
            $taxonomy_themes = $this->taxonomyThemes->pluck('name')->toArray();
        }

        try {
            $coordinates = json_decode($geom)->coordinates;
            $coordinatesCount = count($coordinates);
            $start = $coordinates[0];
            $end = $coordinates[$coordinatesCount - 1];
        } catch (Exception $e) {
            $start = [];
            $end = [];
        }

        try {
            $json = $this->getJson();
            $properties = $json;
        } catch (Exception $e) {
            $properties = null;
        }

        $index_array = explode('_', $index_name);
        $app_id = end($index_array);
        $params = [
            'properties' => $properties,
            'geometry' => json_decode($geom),
            'id' => $this->id,
            'ref' =>  $this->ref,
            'start' =>  $start,
            'end' =>  $end,
            'cai_scale' =>  $this->cai_scale,
            'from' =>  $this->getActualOrOSFValue('from'),
            'to' =>  $this->getActualOrOSFValue('to'),
            'name' =>  $this->name,
            'taxonomyActivities' => $taxonomy_activities,
            'taxonomyWheres' => $taxonomy_wheres,
            'taxonomyThemes' => $taxonomy_themes,
            'feature_image' => $feature_image,
            "distance" => $this->setEmptyValueToZero($this->distance),
            "duration_forward" => $this->setEmptyValueToZero($this->duration_forward),
            "ascent" => $this->setEmptyValueToZero($this->ascent),
            'activities' => $this->taxonomyActivities->pluck('identifier')->toArray(),
            'themes' => $this->taxonomyThemes->pluck('identifier')->toArray(),
            'layers' => $layers,
            'searchable' => json_encode($this->getSearchableString($app_id))
        ];

        $params_update = [
            'index' => 'geohub_' . $index_name,
            'id'    => $this->id,
            'body'  => [
                'doc' => $params
            ]
        ];
        $params_index = [
            'index' => 'geohub_' . $index_name,
            'id'    => $this->id,
            'body'  => $params
        ];

        // NORMAL INDEX
        try {
            $response = $this->elasticClientBuilder($index_name, $params, $params_update, $params_index);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        Log::info($response);
    }

    /**
     * Creates or Updates the EcTrack LOW index on Elastic
     *
     * @param String $index_name
     * @param Array $layers
     * @return void
     */
    public function elasticIndexUpsertLow($index_name, $layers): void
    {
        $tollerance = config('geohub.elastic_low_geom_tollerance');

        Log::info('Update Elastic Low Indexing track ' . $this->id);

        $geom = EcTrack::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(ST_Force2D(ST_SimplifyPreserveTopology(geometry,$tollerance))) as geom")
            )
            ->first()
            ->geom;

        $index_array = explode('_', $index_name);
        $app_id = end($index_array);
        $params = [
            'geometry' => json_decode($geom),
            'id' => $this->id,
            'ref' =>  $this->ref,
            'strokeColor' =>  $this->hexToRgba($this->color),
            'layers' =>  $layers,
            "distance" => $this->setEmptyValueToZero($this->distance),
            "duration_forward" => $this->setEmptyValueToZero($this->duration_forward),
            "ascent" => $this->setEmptyValueToZero($this->ascent),
            'activities' => $this->taxonomyActivities->pluck('identifier')->toArray(),
            'themes' => $this->taxonomyThemes->pluck('identifier')->toArray(),
            'searchable' => $this->getSearchableString($app_id)
        ];

        $params_update = [
            'index' => 'geohub_' . $index_name,
            'id'    => $this->id,
            'body'  => [
                'doc' => $params
            ]
        ];
        $params_index = [
            'index' => 'geohub_' . $index_name,
            'id'    => $this->id,
            'body'  => $params
        ];

        // LOW INDEX
        try {
            $response = $this->elasticClientBuilder($index_name, $params, $params_update, $params_index);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        Log::info($response);
    }

    /**
     * Creates or Updates the EcTrack HIGH index on Elastic
     *
     * @param String $index_name
     * @param Array $layers
     * @return void
     */
    public function elasticIndexUpsertHigh($index_name, $layers): void
    {
        Log::info('Update Elastic HIGH Indexing track ' . $this->id);

        $geom = EcTrack::where('id', '=', $this->id)
            ->select(
                DB::raw("ST_AsGeoJSON(ST_Force2D(geometry)) as geom")
            )
            ->first()
            ->geom;

        $index_array = explode('_', $index_name);
        $app_id = end($index_array);
        $params = [
            'geometry' => json_decode($geom),
            'id' => $this->id,
            'ref' =>  $this->ref,
            'strokeColor' =>  $this->hexToRgba($this->color),
            'layers' =>  $layers,
            "distance" => $this->setEmptyValueToZero($this->distance),
            "duration_forward" => $this->setEmptyValueToZero($this->duration_forward),
            "ascent" => $this->setEmptyValueToZero($this->ascent),
            'activities' => $this->taxonomyActivities->pluck('identifier')->toArray(),
            'themes' => $this->taxonomyThemes->pluck('identifier')->toArray(),
            'searchable' => $this->getSearchableString($app_id)
        ];

        $params_update = [
            'index' => 'geohub_' . $index_name,
            'id'    => $this->id,
            'body'  => [
                'doc' => $params
            ]
        ];
        $params_index = [
            'index' => 'geohub_' . $index_name,
            'id'    => $this->id,
            'body'  => $params
        ];

        // HIGH INDEX
        try {
            $response = $this->elasticClientBuilder($index_name, $params, $params_update, $params_index);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        Log::info($response);
    }

    /**
     * Deletes the EcTrack index on Elastic
     *
     * @param String $index_name
     * @return void
     */
    public function elasticIndexDelete($index_name,$id): void
    {
        $params = ['index' => 'geohub_' . $index_name, 'id' => $id];

        try {
            if (config('app.env') == 'production') {
                Log::info('DELETE Elastic Indexing ' . $index_name . ' track ' . $id);

                $response = Http::withBasicAuth(config('services.elastic.username'), config('services.elastic.password'))->delete(config('services.elastic.host') . '/geohub_' . $index_name . '/_doc/' . $id)->body();
            } else {
                $client = ClientBuilder::create()
                    ->setHosts([config('services.elastic.http')])
                    ->setSSLVerification(false)
                    ->build();

                if ($client->exists(['index' => 'geohub_' . $index_name, 'id' => $id])) {
                    Log::info('DELETE Elastic Indexing ' . $index_name . ' track ' . $id);

                    $response = $client->delete($params);
                    Log::info($response);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function elasticClientBuilder($index_name, $params, $params_update, $params_index)
    {
        if (config('app.env') == 'production') {
            $response = Http::withBasicAuth(config('services.elastic.username'), config('services.elastic.password'))->post(config('services.elastic.host') . '/geohub_' . $index_name . '/_doc/' . $this->id, $params)->body();
        } else {
            $client = ClientBuilder::create()
                ->setHosts([config('services.elastic.http')])
                ->setSSLVerification(false)
                ->build();

            if ($client->exists(['index' => 'geohub_' . $index_name, 'id' => $this->id])) {
                $response = $client->update($params_update);
            } else {
                $response = $client->index($params_index);
            }
        }

        return $response;
    }

    public function updateTrackPBFInfo()
    {
        try {
            $updates = null;
            $ecTrackLayers = $this->getLayersByApp();
            if (is_array($ecTrackLayers)) {
                foreach($ecTrackLayers as $app_id => $layer) {
                    if (!empty($layer)) {
                        $updates = [
                            'layers' => [$app_id => $layer],
                            'activities' => [$app_id => $this->taxonomyActivities->pluck('identifier')->toArray()],
                            'themes' => [$app_id => $this->taxonomyThemes->pluck('identifier')->toArray()],
                            'searchable' => [$app_id => $this->getSearchableString($app_id)]
                        ];
                    }
                }
                if ($updates) {
                    EcTrack::withoutEvents(function () use ($updates) {
                        $this->update($updates);
                    });
                }
            }
        } catch (Exception $e) {
            throw new Exception('ERROR ' . $e->getMessage());
        }
    }
}
