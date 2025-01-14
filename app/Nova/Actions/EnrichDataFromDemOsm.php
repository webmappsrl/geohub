<?php

namespace App\Nova\Actions;

use App\Traits\HandlesData;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class EnrichDataFromDemOsm extends Action
{
    use HandlesData, InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            try {
                // Update with DEM data
                $this->updateDemData($model);

                if ($model->osmid) {
                    // Update with OSM data
                    $osmResult = $this->updateOsmData($model);
                    if (! $osmResult['success']) {
                        throw new \Exception($osmResult['message']);
                    }
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());

                return Action::danger('Failed to update track data: '.$e->getMessage());
            }
        }
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
