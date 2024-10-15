<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateEcTrackElasticIndexJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $ecTrack;
    protected $layer_ids;
    protected $app_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ecTrack, $app_id, $layer_ids)
    {
        $this->ecTrack = $ecTrack;
        $this->app_id = $app_id;
        $this->layer_ids = $layer_ids;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $trackId = $this->ecTrack->id;
        $prefix = config('services.elastic.prefix') ?? 'geohub_app';
        Log::info("Inizio UpdateEcTrackElasticIndexJob per ecTrack ID: {$trackId}");
        if (!empty($this->app_id) && !empty($this->layer_ids)) {
            $indexName = $prefix . '_' . $this->app_id;
            $this->ecTrack->elasticIndex($indexName, $this->layer_ids);
        } else {
            // Recupera i layer associati al track per applicazione
            $ecTrackLayers = $this->ecTrack->getLayersByApp();
            Log::debug("Layer recuperati per ecTrack ID: {$trackId}", ['layers' => $ecTrackLayers]);

            Log::debug("Prefisso dell'indice Elasticsearch utilizzato: {$prefix}");

            if (!empty($ecTrackLayers)) {
                foreach ($ecTrackLayers as $app_id => $layer_ids) {
                    if (!empty($layer_ids)) {
                        $indexName = $prefix . '_' . $app_id;
                        Log::info("Indicizzazione dei layer per app ID: {$app_id} sotto l'indice: {$indexName}", ['layer_ids' => $layer_ids]);

                        // Esegui l'effettiva indicizzazione
                        $this->ecTrack->elasticIndex($indexName, $layer_ids);

                        Log::info("Indicizzazione completata con successo per ecTrack ID: {$trackId} sotto l'indice: {$indexName}");
                    } else {
                        Log::warning("Nessun layer trovato per app ID: {$app_id} sotto ecTrack ID: {$trackId}. Considera l'eliminazione dell'indice.");

                        // Scommenta questo codice se desideri avviare il job per eliminare l'indice quando non si trovano layer
                        // DeleteEcTrackElasticIndexJob::dispatch($ecTrackLayers, $trackId);
                    }
                }
            } else {
                Log::warning("Nessun layer disponibile per ecTrack ID: {$trackId}. Nessuna azione eseguita.");
            }
        }

        Log::info("UpdateEcTrackElasticIndexJob completato per ecTrack ID: {$trackId}");
    }
}
