<?php

namespace App\Jobs;

use App\Models\EcTrack;
use App\Traits\HandlesData;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\WithoutOverlappingBaseJob;

class UpdateTrackFromOsmJob extends WithoutOverlappingBaseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesData;
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
            $result = $this->updateOsmData($this->track);
            if (!$result['success']) {
                Log::error($this->track->id . ' UpdateTrackFromOsmJob: FAILED: ' . $this->track->name . ' (' . $this->track->osmid . '): ' . $result['message']);
            } else {
                Log::info($this->track->id . ' UpdateTrackFromOsmJob: SUCCESS');
            }
        } catch (\Exception $e) {
            Log::error($this->track->id . 'UpdateTrackFromOsmJob: FAILED: ' . $e->getMessage());
        }
    }
}
