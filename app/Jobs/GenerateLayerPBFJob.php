<?php

namespace App\Jobs;

use Illuminate\Queue\Middleware\WithoutOverlapping;

class GenerateLayerPBFJob extends GeneratePBFJob
{
    /**
     * Definisci i middleware per il job.
     *
     * @return array
     */
    public function middleware()
    {
        return [
            // Applica WithoutOverlapping con una chiave unica per limitare la concorrenza
            new WithoutOverlapping($this->getLockKey()),
        ];
    }

    /**
     * Genera una chiave unica per il lock di WithoutOverlapping.
     *
     * @return string
     */
    protected function getLockKey()
    {
        // Utilizza un identificatore unico per ogni tile
        return 'generate-layer-pbf-job-'.$this->z.'-'.$this->x.'-'.$this->y;
    }
}
