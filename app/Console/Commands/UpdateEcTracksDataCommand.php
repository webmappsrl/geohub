<?php

namespace App\Console\Commands;

use App\Jobs\UpdateEcTrackDataJob;
use Illuminate\Console\Command;
use App\Models\App; // Assuming the model for your apps is App
use App\Models\EcTrack;
use App\Models\User;

class UpdateEcTracksDataCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ec-tracks-data {app_id?} {ec_track_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update EC Tracks data with DEM and OSM data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $appId = $this->argument('app_id');
        $ecTrackId = $this->argument('ec_track_id');
        if ($ecTrackId) {
            $track = EcTrack::find($ecTrackId);

            if (!$track) {
                $this->error('UpdateEcTracksDataCommand: EC Track not found');
                return 1;
            }

            // Dispatch the job for the specific track
            UpdateEcTrackDataJob::dispatch($track);

            $this->info('UpdateEcTracksDataCommand: Update job dispatched for track ID ' . $ecTrackId);
        } elseif ($appId) {
            $app = App::find($appId);

            if (!$app) {
                $this->error('UpdateEcTracksDataCommand: App not found');
                return 1;
            }

            $user = User::find($app->user_id);

            if (!$user) {
                $this->error('UpdateEcTracksDataCommand: User not found for the given app');
                return 1;
            }

            $tracks = $user->ecTracks;
            $this->info('UpdateEcTracksDataCommand: start dispatch Update jobs for all tracks(' . count($tracks) . ').');
            foreach ($tracks as $track) {
                // Dispatch the job for each track
                UpdateEcTrackDataJob::dispatch($track);
            }

            $this->info('UpdateEcTracksDataCommand: Update jobs dispatched for all tracks.');
            return 0;
        }
    }
}
