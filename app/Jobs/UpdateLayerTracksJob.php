<?php

namespace App\Jobs;

use App\Models\Layer;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\WithoutOverlappingBaseJob;

class UpdateLayerTracksJob extends WithoutOverlappingBaseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $layer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Layer $layer)
    {
        $this->layer = $layer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Esegui la logica per aggiornare le tracce del layer
        $trackIds = $this->layer->getTracks();
        $this->layer->ecTracks()->sync($trackIds);
    }
}
