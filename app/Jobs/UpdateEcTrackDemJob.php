<?php

namespace App\Jobs;

use App\Traits\HandlesData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateEcTrackDemJob implements ShouldQueue
{
    use Dispatchable;
    use HandlesData;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
            Log::info($this->ecTrack->id.' UpdateEcTrackDemJob: SUCCESS');
        } catch (\Exception $e) {
            Log::error($this->ecTrack->id.'UpdateEcTrackDemJob: FAILED: '.$e->getMessage());
        }
    }
}
