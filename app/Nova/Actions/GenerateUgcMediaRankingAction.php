<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class GenerateUgcMediaRankingAction extends Action
{
    use InteractsWithQueue, Queueable;

    public function name(): string
    {
        return __("Generate Classification");
    }

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $command = 'geohub:get_ranked_users_near_pois'; // Specify the command you want to run
        foreach ($models as $model) {
            $parameters = ['--app_id' => $model->id];
            try {
                Artisan::call($command, $parameters);
                $output = Artisan::output();
                return Action::message('The command has been executed successfully!');
            } catch (\Exception $e) {
                return Action::danger('An error occurred: ' . $e->getMessage());
            }
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
