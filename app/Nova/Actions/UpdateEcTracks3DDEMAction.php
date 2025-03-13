<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Laravel\Nova\Actions\Action;
use App\Jobs\UpdateEcTrackAwsJob;
use Illuminate\Support\Collection;
use App\Jobs\UpdateEcTrack3DDemJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Queue\InteractsWithQueue;

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
                Bus::chain([
                    new UpdateEcTrack3DDemJob($model),
                    new UpdateEcTrackAwsJob($model),
                ])->dispatch();
            } catch (\Exception $e) {
                Log::error('An error occurred during 3D DEM operation: ' . $e->getMessage());
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
