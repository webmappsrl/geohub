<?php

namespace App\Jobs;

use App\Jobs\WithoutOverlappingBaseJob;

class UpdateTrackPBFInfoJob extends WithoutOverlappingBaseJob
{

    protected $track;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($track)
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
        $this->track->updateTrackPBFInfo();
    }
}
