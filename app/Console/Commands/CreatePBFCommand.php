<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePBFJob;
use App\Models\App;
use App\Models\EcTrack;
use App\Models\User;
use App\Services\PBFGenerateTilesAndDispatch;
use App\Services\PBFGenerator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * MBTILES Specs documentation: https://github.com/mapbox/mbtiles-spec/blob/master/1.3/spec.md
 *
 * ATTENTION!!!!!
 *
 * For this command to work first the EcTracks should have the following columns calculated:
 * - layers
 * - themes
 * - activities
 * - searchable
 * This is done by following command:
 * php artisan geohub:update-tracks-for-pbf {app_id} {author}
 *
 * And also the geometry of all EcTracks should have been transformed to EPSG:4326 ('UPDATE ec_tracks SET geometry = ST_SetSRID(geometry, 4326);')
 *
 */
class CreatePBFCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:create_pbf {app_id} {--min : custom min_zoom} {--max= : custom max_zoom}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create PBF files for the app and upload the to AWS.';

    protected $author_id;
    protected $format;
    protected $min_zoom;
    protected $max_zoom;
    protected $app_id;
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
        if (!$app) {
            $this->error('App with id ' . $this->argument('app_id') . ' not found!');
            return;
        }
        if (!$app->app_id) {
            $this->error('This app does not have app_id! Please add app_id. (e.g. it.webmapp.webmapp)');
            return;
        }
        $this->app_id = $app->id;
        $this->author_id = $app->user_id;

        // Min and Max zoom levels can be obtained prom APP configuration or command optional parameters
        // $this->min_zoom = $this->option('min') ?? $app->map_min_zoom;
        // $this->max_zoom = $this->option('max') ?? $app->map_max_zoom;

        $this->min_zoom = $this->option('min') ?? config('geohub.pbf_min_zoom');
        $this->max_zoom = $this->option('max') ?? config('geohub.pbf_max_zoom');

        $bbox = json_decode($app->map_bbox);
        $this->format = 'pbf';

        $generator = new PBFGenerateTilesAndDispatch($this->app_id, $this->author_id);
        $generator->generateTilesAndDispatch($bbox, $this->min_zoom, $this->max_zoom);
    }

    // The deg2num function converts latitude and longitude to tile coordinates at a specific zoom level.
    public function deg2num($lat_deg, $lon_deg, $zoom)
    {
        $lat_rad = deg2rad($lat_deg);
        $n = pow(2, $zoom);
        $xtile = intval(($lon_deg + 180.0) / 360.0 * $n);
        $ytile = intval((1.0 - log(tan($lat_rad) + (1 / cos($lat_rad))) / pi()) / 2.0 * $n);
        return array($xtile, $ytile);
    }

    // The generateTiles function generates all tiles within the bounding box at the specified zoom level.
    public function generateTiles($bbox, $zoom)
    {
        list($minLon, $minLat, $maxLon, $maxLat) = $bbox;
        list($minTileX, $minTileY) = $this->deg2num($maxLat, $minLon, $zoom);
        list($maxTileX, $maxTileY) = $this->deg2num($minLat, $maxLon, $zoom);

        $tiles = [];
        for ($x = $minTileX; $x <= $maxTileX; $x++) {
            for ($y = $minTileY; $y <= $maxTileY; $y++) {
                $tiles[] = [$x, $y, $zoom];
            }
        }
        return $tiles;
    }
}
