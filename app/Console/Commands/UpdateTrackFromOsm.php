<?php

namespace App\Console\Commands;

use App\Http\Facades\OsmClient;
use App\Models\EcTrack;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        $this->info('Updating tracks for user '.$user->name.' ('.$user->email.')'.'...');

        //loop over all the tracks and check if the osmid is not null
        foreach ($tracks as $track) {
            if ($track->osmid != null) {
                $this->info('Updating track '.$track->name.' ('.$track->osmid.')'.'...');
                try {
                    //get the geojson data from OSM
                    $geojson_content = json_decode(OsmClient::getGeojson('relation/'.$track->osmid), true);

                    if (empty($geojson_content['geometry']) || empty($geojson_content['properties'])) {
                        throw new Exception('No geometry or properties found in OSM data');
                    }
                    $geojson_geometry = json_encode($geojson_content['geometry']);
                    $geometry = DB::select("SELECT ST_AsText(ST_Force3D(ST_LineMerge(ST_GeomFromGeoJSON('".$geojson_geometry."')))) As wkt")[0]->wkt;

                } catch (Exception $e) {
                    $this->error('ERROR track '.$track->name.' ('.$track->osmid.')'.$e);
                }

                //update the $track name to the $geojson_content name coming from OSM
                $name_array = [];

                if (array_key_exists('ref', $geojson_content['properties']) && ! empty($geojson_content['properties']['ref'])) {
                    array_push($name_array, $geojson_content['properties']['ref']);
                }
                if (array_key_exists('name', $geojson_content['properties']) && ! empty($geojson_content['properties']['name'])) {
                    array_push($name_array, $geojson_content['properties']['name']);
                }
                $trackname = ! empty($name_array) ? implode(' - ', $name_array) : null;
                $track->name = str_replace('"', '', $trackname);

                //check if ascent, descent, distance duration_forward and duration_backward are not null in the geojson geojson_content and if so, update the $track
                $track->cai_scale = (array_key_exists('cai_scale', $geojson_content['properties']) && $geojson_content['properties']['cai_scale']) ? $geojson_content['properties']['cai_scale'] : $track->cai_scale;
                $track->from = (array_key_exists('from', $geojson_content['properties']) && $geojson_content['properties']['from']) ? $geojson_content['properties']['from'] : $track->from;
                $track->to = (array_key_exists('to', $geojson_content['properties']) && $geojson_content['properties']['to']) ? $geojson_content['properties']['to'] : $track->to;
                $track->ascent = (array_key_exists('ascent', $geojson_content['properties']) && $geojson_content['properties']['ascent']) ? $geojson_content['properties']['ascent'] : $track->ascent;
                $track->descent = (array_key_exists('descent', $geojson_content['properties']) && $geojson_content['properties']['descent']) ? $geojson_content['properties']['descent'] : $track->descent;
                $track->distance = (array_key_exists('distance', $geojson_content['properties']) && $geojson_content['properties']['distance']) ? str_replace(',', '.', $geojson_content['properties']['distance']) : $track->distance;
                //duration forward must be converted to minutes
                if (array_key_exists('duration:forward', $geojson_content['properties']) && $geojson_content['properties']['duration:forward'] != null) {
                    $duration_forward = str_replace('.', ':', $geojson_content['properties']['duration:forward']);
                    $duration_forward = str_replace(',', ':', $duration_forward);
                    $duration_forward = str_replace(';', ':', $duration_forward);
                    $duration_forward = explode(':', $duration_forward);
                    $track->duration_forward = ($duration_forward[0] * 60) + $duration_forward[1];
                }
                //same for duration_backward
                if (array_key_exists('duration:backward', $geojson_content['properties']) && $geojson_content['properties']['duration:backward'] != null) {
                    $duration_backward = str_replace('.', ':', $geojson_content['properties']['duration:backward']);
                    $duration_backward = str_replace(',', ':', $duration_backward);
                    $duration_backward = str_replace(';', ':', $duration_backward);
                    $duration_backward = explode(':', $duration_backward);
                    $track->duration_backward = ($duration_backward[0] * 60) + $duration_backward[1];
                }
                if (array_key_exists('ref', $geojson_content['properties']) && ! empty($geojson_content['properties']['ref'])) {
                    $track->ref = $geojson_content['properties']['ref'];
                }
                $track->skip_geomixer_tech = true;
                $track->geometry = $geometry;
                $track->save();
                $this->info('Track '.$track->name.' ('.$track->osmid.')'.' updated!');
            } else {
                $this->info('Track '.$track->name.' ('.$track->osmid.')'.' has no osmid!');
            }
        }
        $this->info('Tracks for user '.$user->name.' ('.$user->email.')'.' updated!');
    }

    // $s =json_decode(App\Http\Facades\OsmClient::getGeojson('relation/8370439'), true)
}
