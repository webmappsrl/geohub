<?php

namespace App\Nova\Actions;

use App\Providers\HoquServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class RegenerateEcPoi extends Action
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $showOnDetail = true;
    public $showOnTableRow = false;

    public $name = 'Enrich Ec Poi';

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
                $model->updateDataChain($model);
            } catch (\Exception $e) {
                Log::error($model->id . ' RegenerateEcPoi An error occurred during a store operation: ' . $e->getMessage());
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
