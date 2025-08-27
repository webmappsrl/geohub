<?php

namespace App\Nova\Actions;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\TaxonomyPoiType;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class ConvertUgcToEcPoiAction extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Convert To EcPoi';

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $already_exists = [];
        foreach ($models as $model) {
            // Controlla se esiste già un ec_poi_id nelle properties o nel raw_data (per retrocompatibilità)
            $properties = $model->properties ?? [];
            $rawData = isset($model->raw_data) ? json_decode($model->raw_data, true) : [];

            $ecPoiId = $properties['ec_poi_id'] ?? $rawData['ec_poi_id'] ?? null;

            if (! empty($ecPoiId)) {
                $already_exists[] = $model->id;

                continue;
            }

            // Controlla se il POI deve essere condiviso (anche nel form)
            $shareUgcPoi = $properties['share_ugcpoi'] ??
                $properties['form']['share_ugcpoi'] ??
                $rawData['share_ugcpoi'] ?? null;

            if ($shareUgcPoi === 'yes') {
                $ecPoi = new EcPoi;
                $ecPoi->name = $model->name;
                $ecPoi->geometry = $model->geometry;
                $ecPoi->user_id = auth()->user()->id;

                $result = $ecPoi->save();

                if ($result) {

                    // Attach Medias
                    $ugcMedia = $model->ugc_media;
                    if (count($ugcMedia) > 0) {
                        $ecMedia = null;
                        $storage = Storage::disk('public');
                        foreach ($ugcMedia as $count => $media) {
                            try {
                                $mediaName = $ecPoi->id.'_'.last(explode('/', $media['relative_url']));
                                $contents = file_get_contents(public_path('storage/'.$media['relative_url']));
                                $storage->put('ec_media/'.$mediaName, $contents);
                                $ecMedia = new EcMedia(['name' => $mediaName, 'url' => 'ec_media/'.$mediaName, 'geometry' => $media->geometry]);
                                $ecMedia->user_id = auth()->user()->id;
                                $result = $ecMedia->save();
                                if ($count == 0) {
                                    $ecPoi->featureImage()->associate($ecMedia);
                                    $ecPoi->save();
                                } else {
                                    $ecPoi->ecMedia()->attach($ecMedia);
                                }
                            } catch (Exception $e) {
                                Log::error('featureImage: create ec media -> '.$e->getMessage());
                            }
                        }
                    }

                    // Attach Taxonomy Wheres
                    $taxonomyWheres = $model->taxonomy_wheres;
                    if (count($taxonomyWheres) > 0) {
                        $ecPoi->taxonomyWheres()->attach($taxonomyWheres);
                    }

                    // Attach Taxonomy poi types (anche nel form)
                    $poiTypeIdentifier = $properties['waypointtype'] ??
                        $properties['form']['waypointtype'] ??
                        $rawData['waypointtype'] ?? null;

                    if (isset($poiTypeIdentifier)) {
                        $poi_type = TaxonomyPoiType::where('identifier', $poiTypeIdentifier)->first();
                        if ($poi_type) {
                            $ecPoi->taxonomyPoiTypes()->attach($poi_type);
                        }
                    }

                    // attach taxonomy theme
                    if ($model->sku) {
                        $app = App::where('sku', $model->sku)->first();
                        if ($app) {
                            if ($app->taxonomyThemes()->count() > 0) {
                                foreach ($app->taxonomyThemes as $theme) {
                                    $ecPoi->taxonomyThemes()->attach($theme);
                                }
                            }
                        }
                    }

                    // Aggiorna le properties con l'ID dell'EcPoi creato
                    $updatedProperties = $model->properties ?? [];
                    $updatedProperties['ec_poi_id'] = $ecPoi->id;
                    $model->properties = $updatedProperties;
                    $model->save();
                }
            }
        }

        if (count($already_exists) > 0) {
            return Action::message('Conversion completed successfully! The following UgcPois already have an associated EcPoi: '.implode(', ', $already_exists));
        }

        return Action::message('Conversion completed successfully');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
