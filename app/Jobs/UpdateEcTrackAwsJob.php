<?php

namespace App\Jobs;

use App\Models\EcTrack;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UpdateEcTrackAwsJob implements ShouldQueue
{
    use Dispatchable;
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

        //TODO: we need reload ecTrack instance from database because some fields are not updated yet
        $ecTrackId = $ecTrack->id;
        $ecTrack = EcTrack::find($ecTrackId);
        $this->ecTrack = $ecTrack;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $geojson = $this->ecTrack->getGeojson();
        $trackUri = $this->ecTrack->id . '.json';
        try {
            Storage::disk('wmfetracks')->put($trackUri, json_encode($geojson));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
