<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePBFByZoomJob;
use App\Models\App;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

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
 *
 * And also the geometry of all EcTracks should have been transformed to EPSG:4326 ('UPDATE ec_tracks SET geometry = ST_SetSRID(geometry, 4326);')
 *
 */
class GeneratePBFCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pbf:generate {app_id} {--min= : custom min_zoom} {--max= : custom max_zoom}';

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
        if (!$app->id) {
            $this->error('This app does not have app_id! Please add app_id. (e.g. 3)');
            return;
        }
        $this->app_id = $app->id;
        $this->author_id = $app->user_id;

        $this->min_zoom = (int)($this->option('min') ? $this->option('min') : config('geohub.pbf_min_zoom'));
        $this->max_zoom = (int)($this->option('max') ? $this->option('max') : config('geohub.pbf_max_zoom'));

        $bbox = $app->getTracksBBOX();
        if (empty($bbox)) {
            $bbox = json_decode($app->map_bbox);
        }
        if (empty($bbox)) {
            $this->error('This app does not have bounding box! Please add bbox. (e.g. [10.39637,43.71683,10.52729,43.84512])');
            return;
        }
        $this->format = 'pbf';

        // Dispatch the generation process using batches
        $this->dispatchBatches($bbox);

        return 0;
    }

    private function dispatchBatches($bbox)
    {
        $chain = [];
        for ($zoom = $this->min_zoom; $zoom <= $this->max_zoom; $zoom++) {
            $chain[] = new GeneratePBFByZoomJob($bbox, $zoom, $this->app_id, $this->author_id);
        }
        Bus::chain($chain)->onConnection('redis')->onQueue('pbf')->dispatch();
    }
}
