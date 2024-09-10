<?php

namespace App\Nova\Actions;

use App\Jobs\UpdateEcTrackAwsJob;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class GenerateAllAwsTracks extends Action
{
    use InteractsWithQueue, Queueable;


    public function name(): string
    {
        return __("Generete All AWS Tracks");
    }


    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $apps)
    {
        $app = $apps->first();
        $layers = $app->layers;
        $tracks = collect(); // Utilizziamo una Collection per la gestione dei duplicati
        foreach ($layers as $layer) {
            $layerTracks = $layer->getPbfTracks();
            $tracks = $tracks->merge($layerTracks); // Aggiungi le tracce alla Collection
        }
        // Rimuovi i duplicati
        $uniqueTracks = $tracks->unique();

        foreach ($uniqueTracks as $track) {
            UpdateEcTrackAwsJob::dispatch($track);
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
