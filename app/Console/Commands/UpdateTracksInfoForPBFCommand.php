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
    protected $signature = 'geohub:update-tracks-for-pbf {app_id} {author : Set the author that must be assigned to EcFeature created, use email or ID}';

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

        if (is_numeric($this->argument('author'))) {
            try {
                $user = User::find(intval($this->argument('author')));
                $this->author_id = $user->id;
            } catch (Exception $e) {
                throw new Exception('No User found with this ID ' . $this->argument('author'));
            }
        } else {
            try {
                $user = User::where('email', strtolower($this->argument('author')))->first();

                $this->author_id = $user->id;
            } catch (Exception $e) {
                throw new Exception('No User found with this email ' . $this->argument('author'));
            }
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
