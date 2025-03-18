<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\OutSourceFeature;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateHoquScriptsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:generate_hoqu_script
                            {name : deprecated but mandatory ... we are working on it}
                            {--user_id= : All tracks belonging to user identified by id user_id will be stored with ec_track_enrich command}
                            {--user_email= : All tracks belonging to user identified by email user_email will be stored with ec_track_enrich command}
                            {--app_id= : All tracks belonging to app identified by id app_id will be stored with ec_track_enrich command}
                            {--osf_endpoint= : All tracks, pois and media referring to any osf belonging to endpoint are generated with enrich command}
                            {--mbtiles : deprecated}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It generates queue jobs with proper single tasks and chaining to be executed by laravel queue workers';

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

        // Build tracks / poi / media collections
        $tracks = $pois = $media = [];

        // OPTION USER_ID
        if ($this->hasOption('user_id') && ! empty($this->option('user_id'))) {
            $user = User::find($this->option('user_id'));
            if (is_null($user)) {
                $this->info("No user found with id={$this->option('user_id')}");

                return 0;
            }
            $tracks = $user->ecTracks;
            if ($tracks->count() == 0) {
                $this->info("No tracks found corresponding to user {$user->email},ID:{$user->id}");

                return 0;
            }
        }
        // OPTION USER_EMAIL
        elseif ($this->hasOption('user_email') && ! empty($this->option('user_email'))) {
            $user = User::where('email', $this->option('user_email'))->first();
            if (is_null($user)) {
                $this->info("No user found with email={$this->option('user_email')}");

                return 0;
            }
            $tracks = $user->ecTracks;
            if ($tracks->count() == 0) {
                $this->info("No tracks found corresponding to user {$user->email},ID:{$user->id}");

                return 0;
            }
        }
        // OPTION APP_ID
        elseif (! empty($this->option('app_id'))) {
            $app = App::find($this->option('app_id'));
            if (is_null($app)) {
                $this->info("No app found with id={$this->option('app_id')}");

                return 0;
            }
            $tracks = $app->getTracksFromLayer();
            if (count($tracks) == 0) {
                $this->info("No tracks found corresponding to user {$app->name},ID:{$app->id}");

                return 0;
            }
            $tracks = EcTrack::whereIn('id', array_keys($tracks))->get();
        }

        // OPTION OSF_ENDPOINT
        elseif (! empty($this->option('osf_endpoint'))) {
            // currently all osm2cai tracks have osm2cai.cai.it as endpoint domain
            // once the migration is done, this has to be removed after a proper update of import_sync_osm2cai_all.sh script
            // restoring the osm2cai.cai.it domain
            $endpoint = str_replace('https://osm2cai.maphub.it', 'https://osm2cai.cai.it', $this->option('osf_endpoint'));
            $osfs = OutSourceFeature::where('endpoint', $endpoint)->get();
            if ($osfs->count() == 0) {
                $this->info("No OSF found with endpoint {$endpoint}");
                return 0;
            }
            $ids = $osfs->pluck('id')->toArray();
            $tracks = EcTrack::whereIn('out_source_feature_id', $ids)->get();
            $pois = EcPoi::whereIn('out_source_feature_id', $ids)->get();
            $media = EcMedia::whereIn('out_source_feature_id', $ids)->get();

            if (
                $tracks->count() == 0 &&
                $pois->count() == 0 &&
                $media->count() == 0
            ) {
                $this->info("No feature found corresponding to endpoint {$endpoint}");
                return 0;
            }
        } else {
            $this->info('No option set: you have to set one of user_id,user_email,app_id,osf_endpoint.');
            $this->info('Use php artisan geohub:generate_hoqu_script --help to have more details.');

            return 0;
        }

        // MEDIA (skip with --mbtiles)
        if (! $this->option('mbtiles') && ($c = count($media)) > 0) {
            foreach ($media as $item) {
                $item->updateDataChain($item);
            }
            $this->info("Queued {$c} media into the update data chain");
        }

        // POI (skip with --mbtiles)
        if (! $this->option('mbtiles') && ($c = count($pois)) > 0) {
            foreach ($pois as $item) {
                $item->updateDataChain($item);
            }
            $this->info("Queued {$c} poi into the update data chain");
        }

        // TRACKS
        if (($c = $tracks->count()) > 0) {
            foreach ($tracks as $track) {
                $track->updateDataChain($track);
            }
            $this->info("Queued {$c} tracks into the update data chain");
        }

        return 0;
    }
}
