<?php

namespace App\Console\Commands;

use App\Jobs\UpdateEcTrack3DDemJob;
use App\Jobs\UpdateEcTrackDemJob;
use App\Models\App;
use App\Models\EcTrack;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateDEMCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:generate_dem {app_id : The ID of the app} {type : The type of the DEM (3d or dem)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate DEM for EcTrack';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $appId = $this->argument('app_id');
        $type = $this->argument('type');

        $app = App::where('id', $appId)->first();
        if (!$app) {
            $this->error('App with id ' . $appId . ' not found!');
            return;
        }

        // Fetch EcTrack records based on the provided app_id
        $ecTracks = EcTrack::where('user_id', $app->user_id)->get();

        // Dispatch appropriate job based on the type
        foreach ($ecTracks as $ecTrack) {
            if ($type === '3d') {
                UpdateEcTrack3DDemJob::dispatch($ecTrack);
                Log::info('Job 3d DEM dispatched for track id: ' . $ecTrack->id);
            } elseif ($type === 'dem') {
                UpdateEcTrackDemJob::dispatch($ecTrack);
                Log::info('Job DEM dispatched for track id: ' . $ecTrack->id);
            } else {
                $this->error("Invalid DEM type. Please provide either '3d' or 'dem'.");
                return 1; // Exit with error status
            }
        }

        $this->info("DEM generation job dispatched successfully for app_id: $appId and type: $type");
        return 0; // Exit with success status
    }
}
