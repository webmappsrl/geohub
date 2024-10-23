<?php

namespace App\Jobs;

use App\Models\EcTrack;
use App\Traits\HandlesData;
use Illuminate\Support\Facades\Log;
use App\Jobs\WithoutOverlappingBaseJob;

class UpdateCurrentDataJob extends WithoutOverlappingBaseJob
{
    use HandlesData;
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
        try {
            $this->updateCurrentData($this->track);
        } catch (\Exception $e) {
            Log::error($this->track->id . 'UpdateCurrentDataJob: FAILED: ' . $e->getMessage());
        }
    }
}
