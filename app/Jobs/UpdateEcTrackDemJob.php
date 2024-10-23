<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\HandlesData;
use Illuminate\Support\Facades\Log;
use App\Jobs\WithoutOverlappingBaseJob;

class UpdateEcTrackDemJob extends WithoutOverlappingBaseJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use HandlesData;

    protected $ecTrack;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ecTrack)
    {
        $this->ecTrack = $ecTrack;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->updateDemData($this->ecTrack);
            Log::info($this->ecTrack->id . ' UpdateEcTrackDemJob: SUCCESS');
        } catch (\Exception $e) {
            Log::error($this->ecTrack->id . 'UpdateEcTrackDemJob: FAILED: ' . $e->getMessage());
        }
    }
}
