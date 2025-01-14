<?php

namespace App\Nova\Actions;

use App\Jobs\UpdateEcTrack3DDemJob;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class UpdateEcTracks3DDEMAction extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Generate 3D';

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            try {
                UpdateEcTrack3DDemJob::dispatch($model);
            } catch (\Exception $e) {
                Log::error('An error occurred during 3D DEM operation: '.$e->getMessage());
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
