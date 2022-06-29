<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\OutSourceFeature;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateHoquScriptsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:generate_hoqu_script
                            {--user_id= : All tracks belonging to user identified by id user_id will be stored with ec_track_enrich command} 
                            {--user_email= : All tracks belonging to user identified by email user_email will be stored with ec_track_enrich command} 
                            {--app_id= : All tracks belonging to app identified by id app_id will be stored with ec_track_enrich command}
                            {--osf_endpoint= : All tracks, pois and media referring to any osf belonging to endpoint are generated with enrich command}
                            {--mbtiles : Use this option to add ec_track_generate_mbtiles job instead of enrich_ec_track, POI and MEDIA skipped}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It generates a bash scripts with proper single tasks to be executed.
    It saves file in storage/app/hoqu_scripts dir (created if not existing).
    Once it has been generated it can be exceuted.';

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

        $script_name = Carbon::parse('now')->format('Ymd').'_';

        // OPTION USER_ID
        if($this->hasOption('user_id') && !empty($this->option('user_id'))) {
            $user = User::find($this->option('user_id'));
            if(is_null($user)) {
                $this->info("No user found with id={$this->option('user_id')}");
                return 0;
            }
            $tracks = $user->ecTracks;
            if($tracks->count()==0) {
                $this->info("No tracks found corresponding to user {$user->email},ID:{$user->id}");
                return 0;
            }
            $script_name .= 'user_id_'.$user->id;
        }
        // OPTION USER_EMAIL
        else if($this->hasOption('user_email') && !empty($this->option('user_email'))) {
            $user = User::where('email',$this->option('user_email'))->first();
            if(is_null($user)) {
                $this->info("No user found with email={$this->option('user_email')}");
                return 0;
            }
            $tracks = $user->ecTracks;
            if($tracks->count()==0) {
                $this->info("No tracks found corresponding to user {$user->email},ID:{$user->id}");
                return 0;
            }
            $script_name .= 'user_email_'.$user->email;
        }
        // OPTION APP_ID        
        else if(!empty($this->option('app_id'))) {
            $app = App::find($this->option('app_id'));
            if(is_null($app)) {
                $this->info("No app found with id={$this->option('app_id')}");
                return 0;
            }
            $tracks = $app->getTracksFromLayer();
            if(count($tracks)==0) {
                $this->info("No tracks found corresponding to user {$app->name},ID:{$app->id}");
                return 0;
            }
            $tracks = EcTrack::whereIn('id',array_keys($tracks))->get();
            $script_name .= 'app_'.$app->id;
        }

        // OPTION OSF_ENDPOINT
        else if (!empty($this->option('osf_endpoint'))) {
            $osfs = OutSourceFeature::where('endpoint',$this->option('osf_endpoint'))->get();
            if($osfs->count()==0) {
                $this->info("No OSF found with endpoint {$this->option('osf_endpoint')}");
                return 0;
            }
            $ids = $osfs->pluck('id')->toArray();
            $tracks = EcTrack::whereIn('out_source_feature_id',$ids)->get();
            $pois = EcPoi::whereIn('out_source_feature_id',$ids)->get();
            $media = EcMedia::whereIn('out_source_feature_id',$ids)->get();

            if($tracks->count() == 0 &&
            $pois->count() == 0 &&
            $media->count() == 0 ) {
                $this->info("No feature found corresponding to endpoint {$this->option('osf_endpoint')}");
                return 0;
            }

            $script_name .= 'osf_'.preg_replace("(^https?://)","",$this->option('osf_endpoint'));
        }
        else {
            $this->info('No option set: you have to set one of user_id,user_email,app_id,osf_endpoint.');
            $this->info('Use php artisan geohub:generate_hoqu_script --help to have more details.');
            return 0;
        }

        // Creates Script content
        $script_content = "#!/bin/bash \n";
        // MEDIA (skip with --mbtiles)
        if(!$this->option('mbtiles') && count($media)>0) {
            foreach($media as $item) {
                $script_content .= "php artisan geohub:hoqu_store enrich_ec_media {$item->id}\n";
            }
        }

        // POI (skip with --mbtiles)
        if(!$this->option('mbtiles') && count($pois)>0) {
            foreach($pois as $item) {
                $script_content .= "php artisan geohub:hoqu_store enrich_ec_poi {$item->id}\n";
            }
        }

        // TRACKS
        if($tracks->count()>0) {
            foreach ($tracks as $track) {
                if($this->option('mbtiles')) {
                    $script_content .= "php artisan geohub:hoqu_store ec_track_generate_mbtiles {$track->id}\n";
                }
                else {
                    $script_content .= "php artisan geohub:hoqu_store enrich_ec_track {$track->id}\n";                }
            }
        }

        // WRITE TO FILE
        $dir = storage_path('app/hoqu_scripts');
        if(!file_exists($dir)) {
            $this->info("Directory $dir does not exist: creating;");
            system("mkdir -p $dir");
        }

        $path = "{$dir}/{$script_name}.sh";
        $this->info("Writing file $path");
        file_put_contents($path,$script_content);

        return 0;
    }
}
