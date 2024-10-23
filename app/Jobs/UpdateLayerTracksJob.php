<?php

namespace App\Jobs;

use App\Models\Layer;
use App\Jobs\WithoutOverlappingBaseJob;

class UpdateLayerTracksJob extends WithoutOverlappingBaseJob
{
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
