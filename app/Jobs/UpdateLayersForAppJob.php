<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Layer;
use App\Models\App;
use Illuminate\Support\Facades\Log;

class UpdateLayersForAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($appId)
    {
        $this->appId = $appId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Recupera l'istanza dell'app utilizzando l'app_id
        $app = App::with('layers')->find($this->appId);

        if (!$app) {
            Log::error("App con id {$this->appId} non trovata.");
            return;
        }

        // Recupera tutti i layer associati all'app
        $layers = $app->layers;

        if ($layers->isEmpty()) {
            Log::info("Nessun layer associato trovato per l'app con id {$this->appId}.");
            return;
        }

        // Loop attraverso tutti i layer e aggiorna le tracce
        foreach ($layers as $layer) {
            try {
                $trackIds = $layer->getTracks();
                $layer->ecTracks()->sync($trackIds);

                // Logga l'aggiornamento completato
                Log::info("Layer ID: {$layer->id} aggiornato con successo.");
            } catch (\Exception $e) {
                // Logga eventuali errori
                Log::error("Errore durante l'aggiornamento del layer ID: {$layer->id}. Errore: " . $e->getMessage());
            }
        }

        Log::info("Tutti i layer per l'app con id {$this->appId} sono stati aggiornati.");
    }
}
