<?php

namespace App\Nova\Actions;

use App\Jobs\UpdateEcTrackDemJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class UpdateEcTracksDEMAction extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Generate DEM';

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
            try {
                UpdateEcTrackDemJob::dispatch($model);
            } catch (\Exception $e) {
                Log::error('An error occurred during DEM operation: ' . $e->getMessage());
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
