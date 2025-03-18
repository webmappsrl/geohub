<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class CopyUgc extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $targetUserId = auth()->user()->id; // Ottieni l'ID dell'utente loggato

        foreach ($models as $model) {
            $newTrack = $model->replicate();
            $newTrack->user_id = $targetUserId;
            $newTrack->save();
        }

        return Action::message('All UGC tracks have been copied successfully!');
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
