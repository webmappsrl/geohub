<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Layer;
use App\Models\App;
use Illuminate\Support\Facades\Log;

class UpdateLayersForApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'layers:update {app_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all layers for the given app_id';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Ottieni l'app_id passato come argomento
        $appId = $this->argument('app_id');

        // Recupera l'istanza dell'app utilizzando l'app_id
        $app = App::with('layers')->find($appId);

        if (!$app) {
            $this->error("App con id $appId non trovata.");
            return 1; // Errore
        }

        // Recupera tutti i layer associati all'app
        $layers = $app->layers;

        if ($layers->isEmpty()) {
            $this->info("Nessun layer associato trovato per l'app con id $appId.");
            return 0; // Successo, ma nessun layer
        }

        // Loop attraverso tutti i layer e aggiorna le tracce
        foreach ($layers as $layer) {
            try {
                $trackIds = $layer->getTracks();
                $layer->ecTracks()->sync($trackIds);

                // Logga l'aggiornamento completato
                $this->info("Layer ID: {$layer->id} aggiornato con successo.");
            } catch (\Exception $e) {
                // Logga eventuali errori
                Log::error("Errore durante l'aggiornamento del layer ID: {$layer->id}. Errore: " . $e->getMessage());
                $this->error("Errore durante l'aggiornamento del layer ID: {$layer->id}");
            }
        }

        $this->info("Tutti i layer per l'app con id $appId sono stati aggiornati.");
        return 0; // Successo
    }
}
