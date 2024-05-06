<?php

namespace App\Nova\Actions;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcPoi;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class ConvertUgcToEcPoiAction extends Action
{
    use InteractsWithQueue, Queueable;

    public $name='Convert To EcPoi';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {

            if (isset($model->raw_data) && property_exists(json_decode($model->raw_data), 'share_ugcpoi') && json_decode($model->raw_data)->share_ugcpoi === 'yes') {
                $ecPoi = new EcPoi();
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
                                $mediaName = $result.'_'.last(explode('/', $media['relative_url']));
                                $contents = file_get_contents(asset('storage/'.$media['relative_url']));
                                $storage->put($mediaName, $contents); 
                                $ecMedia = new EcMedia(['name' => $mediaName, 'url' => $media->url, 'geometry' => $media->geometry]);
                                $ecMedia->user_id = auth()->user()->id;
                                $ecMedia->save();
                                if ($count == 0) {
                                    $ecPoi->featureImage()->associate($ecMedia);
                                } else {
                                    $ecPoi->ecMedia()->attach($ecMedia);
                                }
                            } catch (Exception $e) {
                                Log::error("featureImage: create ec media -> " . $e->getMessage());
                            }
                        }
                        
                        $ecPoi->featureImage()->associate($ecMedia);
                    }

                    // Attach Taxonomy Wheres
                    $taxonomyWheres = $model->taxonomy_wheres;
                    if (count($taxonomyWheres) > 0) {
                        $ecPoi->taxonomyWheres()->attach($taxonomyWheres);
                    }

                    // Attach Taxonomy poi types
                    if (isset($model->raw_data) && property_exists(json_decode($model->raw_data), 'waypointtype')) {
                        $poi_type = json_decode($model->raw_data)->waypointtype;
                    }
                    if (isset($poi_type)) {
                        $ecPoi->taxonomyPoiTypes()->attach($poi_type);
                    }

                    // attach taxonomy theme
                    if ($model->app_id) {
                        $app = App::where('app_id', $model->app_id)->first();
                        if ($app) {
                            if ($app->taxonomyThemes()->count() > 0) {
                                foreach ($app->taxonomyThemes as $theme) {
                                    $ecPoi->taxonomyThemes()->attach($theme);
                                }
                            }
                        }
                    }

                    $model->raw_data = DB::raw("jsonb_set(raw_data, '{ec_poi_id}', '\"{$ecPoi->id}\"')");
                    $model->save();
                }
            }
        }

        return $ecPoi;
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
