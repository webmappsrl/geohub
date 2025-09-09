<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use App\Jobs\BuildIconsJsonJob;

class GenerateAppIconsAction extends Action
{
    use InteractsWithQueue, Queueable;

    public function name(): string
    {
        return __('Generate Icons');
    }

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            BuildIconsJsonJob::dispatch($model);
        }

        return Action::message('job executed');
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
