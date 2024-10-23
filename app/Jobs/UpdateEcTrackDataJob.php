<?php

namespace App\Jobs;

use App\Models\EcTrack;
use App\Jobs\WithoutOverlappingBaseJob;

class UpdateEcTrackDataJob extends WithoutOverlappingBaseJob
{
    protected $track;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(EcTrack $track)
    {
        $this->track = $track;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->track->updateDataChain($this->track);
    }
}
