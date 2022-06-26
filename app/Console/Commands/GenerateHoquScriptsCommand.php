<?php

namespace App\Console\Commands;

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
        else {
            $this->info('No option set: you have to set one of user_id,user_email,layer_id,app_id,osf_endpoint.');
            $this->info('Use php artisan geohub:generate_hoqu_script --help to have more details.');
            return 0;
        }

        // Creates Script content
        $script_content = "#!/bin/bash \n";
        // MEDIA (skip with --mbtiles)

        // POI (skip with --mbtiles)

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
