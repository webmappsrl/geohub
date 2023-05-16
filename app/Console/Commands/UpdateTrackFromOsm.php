<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\EcTrack;
use App\Http\Facades\OsmClient;
use Illuminate\Console\Command;

class UpdateTrackFromOsm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:update_track_from_osm {user_email} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loops through all the tracks belonging to the user identified by user_email. If the parameter osmid is not null, it performs some sync operations from OSM to GEOHUB.';

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

        // if no user_email is provided, exit
        if ($this->argument('user_email') == null) {
            $this->error('Please provide a user email');
            return 0;
        }
        //find all the tracks that belongsto the user identified by user_email
        $userEmail = $this->argument('user_email');
        $user = User::where('email', $userEmail)->first();
        $tracks = EcTrack::where('user_id', $user->id)->get();

        $this->info('Updating tracks for user ' . $user->name . ' (' . $user->email . ')' . '...');

        //loop over all the tracks and check if the osmid is not null  
        foreach ($tracks as $track) {
            if ($track->osmid != null) {
                $this->info('Updating track ' . $track->name . ' (' . $track->osmid . ')' . '...');
                //get the geojson data from OSM
                $data = json_decode(OsmClient::getGeojson('relation/' . $track->osmid), true);
                //update the $track name to the $data name coming from OSM
                $names = json_decode($track->name, true);
                if (key_exists('name',$data['properties']) && $data['properties']['name']) {
                    $names = [];
                    $names['it'] = $data['properties']['name'];
                }
                $track->name = $names;

                //check if ascent, descent, distance duration_forward and duration_backward are not null in the geojson data and if so, update the $track
                $track->ascent = (key_exists('ascent',$data['properties']) && $data['properties']['ascent'] ) ? $data['properties']['ascent'] : $track->ascent;
                $track->descent = (key_exists('descent',$data['properties']) && $data['properties']['descent'] ) ? $data['properties']['descent'] : $track->descent;
                $track->distance = (key_exists('distance',$data['properties']) && $data['properties']['distance'] ) ?str_replace(',','.',$data['properties']['distance']) : $track->distance;
                //duration forward must be converted to minutes
                if (key_exists('duration:forward',$data['properties']) && $data['properties']['duration:forward'] != null) {
                    $duration_forward = str_replace('.',':',$data['properties']['duration:forward']);
                    $duration_forward = explode(':', $duration_forward);
                    $track->duration_forward = ($duration_forward[0] * 60) + $duration_forward[1];
                }
                //same for duration_backward
                if (key_exists('duration:backward',$data['properties']) && $data['properties']['duration:backward'] != null) {
                    $duration_backward = str_replace('.',':',$data['properties']['duration:forward']);
                    $duration_backward = explode(':', $duration_backward);
                    $track->duration_backward = ($duration_backward[0] * 60) + $duration_backward[1];
                }
                $track->save();
                $this->info('Track ' . $track->name . ' (' . $track->osmid . ')' . ' updated!');
            } else {
                $this->info('Track ' . $track->name . ' (' . $track->osmid . ')' . ' has no osmid!');
            }
        }
        $this->info('Tracks for user ' . $user->name . ' (' . $user->email . ')' . ' updated!');
    }

    // $s =json_decode(App\Http\Facades\OsmClient::getGeojson('relation/8370439'), true)
}
