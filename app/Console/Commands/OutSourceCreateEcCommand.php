<?php

namespace App\Console\Commands;

use App\Models\EcTrack;
use App\Models\OutSourceTrack;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyTheme;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
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
                            {provider : Select the provider to be used for import (sicai or osm)}
                            {user_id : User ID to be assigned to the EC tracks}
                            {--activity= : Activity identifier. If set Ec Tracks will be attached to it}
                            {--theme= : Theme identifier. If set Ec Tracks will be attached to it}
                            ';

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
        if (is_null($user)) {
            throw new Exception('No USER');
        }
        Auth::login($user);
        $provider = $this->argument('provider');
        switch ($provider) {
            case 'sicai':
                $oss = OutSourceTrack::where('provider', 'App\Providers\OutSourceSentieroItaliaProvider')->get();
                break;
            case 'osm':
                $oss = OutSourceTrack::where('provider', 'App\Providers\OutSourceOSMProvider')->get();
                break;

            default:
                throw new Exception("Invalid provider $provider", 1);
                break;
        }
        // Taxonomies
        $activity_id = $theme_id = null;
        if ($this->option('activity')) {
            $activity = TaxonomyActivity::where('identifier', $this->option('activity'))->first();
            if (! is_null($activity)) {
                $activity_id = $activity->id;
            } else {
                throw new Exception('Invalid Activity IDENTIFER: '.$this->option('activity'), 1);
            }
        }
        if ($this->option('theme')) {
            $theme = TaxonomyTheme::where('identifier', $this->option('theme'))->first();
            if (! is_null($theme)) {
                $theme_id = $theme->id;
            } else {
                throw new Exception('Invalid Theme IDENTIFER: '.$this->option('theme'), 1);
            }
        }
        if ($oss->count() > 0) {
            foreach ($oss as $os) {
                Log::info("\n\nImporting OS {$os->id}");

                // Geometry
                $geometry = null;
                $res = DB::select(DB::raw('SELECT ST_ASGeoJSON(geometry) as geojson from out_source_features where id='.$os->id));
                if (isset($res[0]->geojson)) {
                    $geojson = json_decode($res[0]->geojson, true);
                    if (is_array($geojson)) {
                        Log::info("Importing OS {$os->id}");
                        $ec_track = new EcTrack();
                        $ec_track->name = $os->getName();
                        $ec_track->user_id = $this->argument('user_id');
                        $ec_track->out_source_feature_id = $os->id;

                        // Other META
                        $tags = $os->getNormalizedTags();
                        if (isset($tags['ref'])) {
                            $ec_track->ref = $tags['ref'];
                        }
                        if (isset($tags['cai_scale'])) {
                            $ec_track->cai_scale = $tags['cai_scale'];
                        }
                        if (isset($tags['from'])) {
                            $ec_track->from = $tags['from'];
                        }
                        if (isset($tags['to'])) {
                            $ec_track->to = $tags['to'];
                        }

                        // Convert MultiLine to Line and cast to 3d
                        $geojson['type'] = 'LineString';
                        $geojson['coordinates'] = $geojson['coordinates'][0];
                        $geojson = json_encode($geojson);
                        $ec_track->geometry = DB::raw("ST_Force3D((ST_GeomFromGeoJSON('{$geojson}')))");
                        $ec_track->save();

                        // TAXONOMIES
                        if (isset($activity_id)) {
                            $ec_track->taxonomyActivities()->attach($activity_id);
                        }
                        if (isset($theme_id)) {
                            $ec_track->taxonomyThemes()->attach($theme_id);
                        }
                    }
                } else {
                    Log::info('WARNING NO GEOMETRY: SKIP');
                }
            }

        } else {
            Log::info('NO feature found');
        }

        return 0;
    }
}
