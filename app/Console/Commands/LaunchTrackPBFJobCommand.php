<?php

namespace App\Console\Commands;

use App\Jobs\UpdateTrackPBFJob;
use App\Models\EcTrack;
use Illuminate\Console\Command;

class LaunchTrackPBFJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:launch_track_pbf_job {track_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Launch the UpdateTrackPBFJob for a specific track.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $trackId = $this->argument('track_id');
        $track = EcTrack::find($trackId);

        if (!$track) {
            $this->error('Track with id ' . $trackId . ' not found!');
            return 1; // return a non-zero code for failure
        }

        // Dispatch the job
        UpdateTrackPBFJob::dispatch($track);

        $this->info('UpdateTrackPBFJob for track id ' . $trackId . ' has been dispatched.');
        return 0; // return zero for success
    }
}
