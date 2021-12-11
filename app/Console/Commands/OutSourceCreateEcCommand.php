<?php

namespace App\Console\Commands;

use App\Models\EcTrack;
use App\Models\OutSourceTrack;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutSourceCreateEcCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:out_source_create_ec 
                            {user_id : User_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create EcTracks from specific source identified by provider and url and set owner to user_id';

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
        $user = User::find($this->argument('user_id'));
        if(is_null($user)){
            throw new Exception('No USER');
        }
        $oss = OutSourceTrack::all();
        if($oss->count()>0) {
            foreach($oss as $os) {
                Log::info("\n\nImporting OS {$os->id}");
                
                // Geometry
                $geometry = null;
                $res = DB::select(DB::raw('SELECT ST_ASGeoJSON(geometry) as geojson from out_source_features where id='.$os->id));
                if(isset($res[0]->geojson)) {
                    $geojson=json_decode($res[0]->geojson,true);
                    if(is_array($geojson)) {
                        Log::info("Importing OS {$os->id}");
                        $ec_track = new EcTrack();
                        $ec_track->name=$os->getName();
                        $ec_track->user_id=$this->argument('user_id');
                        $ec_track->out_source_feature_id=$os->id;

                        // Convert MultiLine to Line and cast to 3d 
                        $geojson['type']='LineString';
                        $geojson['coordinates']=$geojson['coordinates'][0];
                        $geojson=json_encode($geojson);
                        $ec_track->geometry=DB::raw("ST_Force3D((ST_GeomFromGeoJSON('{$geojson}')))");
                        $ec_track->save();
                    }
                } else {
                    Log::info("WARNING NO GEOMETRY: SKIP");
                }
            }
        }
        return 0;
    }
}
