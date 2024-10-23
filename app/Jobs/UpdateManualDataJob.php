<?php

namespace App\Jobs;

use App\Models\EcTrack;
use App\Traits\HandlesData;
use Illuminate\Support\Facades\Log;
use App\Jobs\WithoutOverlappingBaseJob;

class UpdateManualDataJob extends WithoutOverlappingBaseJob
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
            $this->updateManualData($this->track);
            Log::info($this->track->id . ' UpdateManualDataJob: SUCCESS');
        } catch (\Exception $e) {
            Log::error($this->track->id . 'UpdateManualDataJob: FAILED: ' . $e->getMessage());
        }
    }
}
