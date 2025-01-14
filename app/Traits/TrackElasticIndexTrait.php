<?php

namespace App\Traits;

use App\Models\EcTrack;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait TrackElasticIndexTrait
{
    use ElasticIndexTrait;

    /**
     * Deletes the EcTrack index on Elastic
     *
     * @param  string  $index_name
     */
    public function elasticIndexDelete($index_name, $id): void
    {
        $params = ['index' => $index_name, 'id' => $id];

        try {
            $client = $this->getClient();

            if ($client->exists(['index' => $index_name, 'id' => $id])) {
                Log::info('DELETE Elastic Indexing '.$index_name.' track '.$id);
                $response = $client->delete($params);
                Log::info($response);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function elasticClientBuilder($index_name, $params, $params_update, $params_index)
    {
        $client = $this->getClient();

        try {
            if ($client->exists(['index' => $index_name, 'id' => $this->id])) {
                $response = $client->update($params_update);
            } else {
                $response = $client->index($params_index);
            }
        } catch (\Exception $e) {
            Log::error('ElasticSearch Error: '.$e->getMessage());
            throw $e; // Rilancia l'eccezione per gestirla altrove, se necessario
        }

        return $response;
    }

    public function updateTrackPBFInfo()
    {
        try {
            $updates = null;
            $ecTrackLayers = $this->associatedLayers()->get();
            foreach ($ecTrackLayers as $layer) {
                if (! empty($layer)) {
                    $updates['layers'][$layer->app_id] = $layer->id;
                    $updates['activities'][$layer->app_id] = $this->getTaxonomyArray($this->taxonomyActivities);
                    $updates['themes'][$layer->app_id] = $this->getTaxonomyArray($this->taxonomyThemes);
                    $updates['searchable'][$layer->app_id] = $this->getSearchableString($layer->app_id);
                }
            }
            if ($updates) {
                EcTrack::withoutEvents(function () use ($updates) {
                    $this->update($updates);
                });
            }
        } catch (Exception $e) {
            throw new Exception('ERROR '.$e->getMessage());
        }
    }

    public function elasticIndex($index_name, $layers): void
    {
        Log::info('Update Elastic Indexing track '.$this->id);

        $geom = $this->getGeometry();

        $feature_image = $this->getFeatureImage();

        $taxonomy_activities = $this->getTaxonomyArray($this->taxonomyActivities);
        log::info($taxonomy_activities);
        $taxonomy_wheres = $this->getTaxonomyWheres();
        $taxonomy_themes = $this->getTaxonomyArray($this->taxonomyThemes);

        [$start, $end] = $this->getStartEndCoordinates($geom);

        // Preparazione dei parametri per ElasticSearch
        $params = $this->buildParamsArray(
            $index_name,
            $layers,
            $start,
            $end,
            $feature_image,
            $taxonomy_activities,
            $taxonomy_wheres,
            $taxonomy_themes
        );
        $this->elasticIndexDoc($index_name, $this->id, $params);
    }

    private function getGeometry()
    {
        $geom_query = 'ST_AsGeoJSON(geometry) as geom';

        return EcTrack::where('id', '=', $this->id)
            ->select(
                DB::raw($geom_query)
            )
            ->first()
            ->geom;
    }

    private function getFeatureImage()
    {
        $feature_image = '';
        if (isset($this->featureImage->thumbnails)) {
            $sizes = json_decode($this->featureImage->thumbnails, true);
            if (isset($sizes['400x200'])) {
                $feature_image = $sizes['400x200'];
            } elseif (isset($sizes['225x100'])) {
                $feature_image = $sizes['225x100'];
            }
        }

        return $feature_image;
    }

    private function getTaxonomyArray($taxonomyCollection)
    {
        return $taxonomyCollection->count() > 0 ? array_values(array_unique($taxonomyCollection->pluck('identifier')->toArray())) : [];
    }

    private function getTaxonomyWheres()
    {
        $taxonomy_wheres = [];
        if ($this->taxonomyWheres->count() > 0) {
            if ($this->taxonomy_wheres_show_first) {
                $taxonomy_wheres = $this->taxonomyWheres->pluck('name', 'id')->toArray();
                $first_show_name = $taxonomy_wheres[$this->taxonomy_wheres_show_first];
                unset($taxonomy_wheres[$this->taxonomy_wheres_show_first]);
                $taxonomy_wheres = array_values($taxonomy_wheres);
                array_push($taxonomy_wheres, $first_show_name);
            } else {
                $taxonomy_wheres = $this->taxonomyWheres->pluck('name')->toArray();
            }
        }

        return array_values(array_unique($taxonomy_wheres));
    }

    private function getStartEndCoordinates($geom)
    {
        try {
            $coordinates = json_decode($geom)->coordinates;
            $coordinatesCount = count($coordinates);
            $start = $coordinates[0];
            $end = $coordinates[$coordinatesCount - 1];
        } catch (Exception $e) {
            $start = [];
            $end = [];
        }

        return [[$start[0], $start[1]], [$end[0], $end[1]]];
    }

    private function getJsonProperties()
    {
        try {
            return $this->getJson();
        } catch (Exception $e) {
            return null;
        }
    }

    private function buildParamsArray($index_name, $layers, $start, $end, $feature_image, $taxonomy_activities, $taxonomy_wheres, $taxonomy_themes)
    {
        $index_array = explode('_', $index_name);
        $app_id = end($index_array);

        return [
            'id' => $this->id,
            'ref' => $this->ref,
            'start' => $start,
            'end' => $end,
            'cai_scale' => $this->cai_scale,
            'from' => $this->getActualOrOSFValue('from'),
            'to' => $this->getActualOrOSFValue('to'),
            'name' => $this->name,
            'taxonomyActivities' => $taxonomy_activities,
            'taxonomyWheres' => $taxonomy_wheres,
            'taxonomyThemes' => $taxonomy_themes,
            'feature_image' => $feature_image,
            'strokeColor' => $this->hexToRgba($this->color),
            'distance' => $this->setEmptyValueToZero($this->distance),
            'duration_forward' => $this->setEmptyValueToZero($this->duration_forward),
            'ascent' => $this->setEmptyValueToZero($this->ascent),
            'activities' => $this->taxonomyActivities->pluck('identifier')->toArray(),
            'themes' => $this->taxonomyThemes->pluck('identifier')->toArray(),
            'layers' => $layers,
            'searchable' => json_encode($this->getSearchableString($app_id)),
        ];
    }

    private function executeElasticIndexing($index_name, $params)
    {
        $params_update = [
            'index' => 'geohub_'.$index_name,
            'id' => $this->id,
            'body' => [
                'doc' => $params,
            ],
        ];
        $params_index = [
            'index' => 'geohub_'.$index_name,
            'id' => $this->id,
            'body' => $params,
        ];

        try {
            $response = $this->elasticClientBuilder($index_name, $params, $params_update, $params_index);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
        Log::info($response);
    }
}
