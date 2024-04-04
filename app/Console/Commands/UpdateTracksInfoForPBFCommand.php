<?php

namespace App\Console\Commands;

use App\Jobs\UpdateTrackPBFInfoJob;
use App\Models\App;
use App\Models\EcTrack;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateTracksInfoForPBFCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:update-tracks-for-pbf {app_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update tracks info for PBF files for the app and upload the to AWS';

    protected $app_id;
    protected $author_id;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $app = App::where('id', $this->argument('app_id'))->first();

        $this->app_id = $this->argument('app_id');

        if (!$app) {
            $this->error('App with id ' . $this->argument('app_id') . ' not found!');
            return 0;
        }
        try {
            $this->author_id = $app->user_id;
        } catch (Exception $e) {
            throw new Exception('No User found for this app ' . $this->app_id);
        }

        $tracks = DB::table('ec_tracks')
            ->select('id')
            ->where('user_id', $this->author_id)
            ->get();

        foreach ($tracks as $c => $track_id) {
            $track = EcTrack::find($track_id->id);
            try {
                UpdateTrackPBFInfoJob::dispatch($track);
                Log::info($c . '/'. count($tracks));
            } catch (\Exception $e) {
                Log::error('An error occurred during updating EcTrack PBF Info dispatch: ' . $e->getMessage());
            }
        }

        return 0;
    }
}
