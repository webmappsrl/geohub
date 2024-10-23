<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Storage;
use App\Jobs\WithoutOverlappingBaseJob;

class UpdateEcTrackAwsJob extends WithoutOverlappingBaseJob
{
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
        $geojson = $this->ecTrack->getGeojson();
        $trackUri = $this->ecTrack->id . '.json';
        Storage::disk('wmfetracks')->put($trackUri, json_encode($geojson));
    }
}
