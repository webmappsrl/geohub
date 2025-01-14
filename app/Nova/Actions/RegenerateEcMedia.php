<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class RegenerateEcMedia extends Action
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $showOnDetail = true;

    public $showOnTableRow = true;

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            try {
                $model->updateDataChain($model);
            } catch (\Exception $e) {
                Log::error($model->id.' RegenerateEcMedia An error occurred during a store operation: '.$e->getMessage());
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
