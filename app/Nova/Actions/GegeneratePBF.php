<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class GeneratePBF extends Action
{
    use InteractsWithQueue, Queueable;

    public function name(): string
    {
        return __('Generete PBF');
    }

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $appId = $models->first()->id;

        try {
            Artisan::call('geohub:update-tracks-for-pbf', [
                'app_id' => $appId,
            ]);
            Log::info("Comando geohub:geohub:update-tracks-for-pbf eseguito con successo per l'app ID: {$appId}");
        } catch (\Exception $e) {
            Log::error('Errore durante l\'esecuzione dei comandi: '.$e->getMessage());

            return Action::danger('Errore durante l\'esecuzione dei comandi.');
        }
        try {
            Artisan::call('pbf:generate', [
                'app_id' => $appId,
            ]);
            Log::info("Comando geohub:create_pbf eseguito con successo per l'app ID: {$appId}");
        } catch (\Exception $e) {
            Log::error('Errore durante l\'esecuzione dei comandi: '.$e->getMessage());

            return Action::danger('Errore durante l\'esecuzione di geohub:create_pbf');
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
