<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\WithoutOverlappingBaseJob;

class UpdateTrackPBFInfoJob extends WithoutOverlappingBaseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
