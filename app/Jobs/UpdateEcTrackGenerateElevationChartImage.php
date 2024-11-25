<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateEcTrackGenerateElevationChartImage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $ecTrack;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ecTrack)
    {
        $this->ecTrack = $ecTrack;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $geojson = $this->ecTrack->getTrackGeometryGeojson();
        if (!isset($geojson['properties']['id']))
            throw new Exception('The geojson id is not defined');

        $path = $this->generateElevationChartImage($geojson);
        $this->ecTrack->elevation_chart_image = $path;
        $this->ecTrack->saveQuietly();
    }

    /**
     * Generate the elevation chart image for the ec track
     * Imported from geomixer
     *
     * @param array $geojson
     *
     * @return string with the generated image path
     * @throws Exception when the generation fail
     */
    public function generateElevationChartImage(array $geojson): string
    {
        if (!isset($geojson['properties']['id']))
            throw new Exception('The geojson id is not defined');

        $localDisk = Storage::disk('local');
        $ecMediaDisk = Storage::disk('ecmedia');

        if (!$localDisk->exists('elevation_charts')) {
            $localDisk->makeDirectory('elevation_charts');
        }
        if (!$localDisk->exists('geojson')) {
            $localDisk->makeDirectory('geojson');
        }

        $id = $geojson['properties']['id'];

        $localDisk->put("geojson/$id.geojson", json_encode($geojson));

        $src = $localDisk->path("geojson/$id.geojson");
        $dest = $localDisk->path("elevation_charts/$id.svg");

        $cmd = config('geohub.node_executable') . " node/jobs/build-elevation-chart.js --geojson=$src --dest=$dest --type=svg";

        Log::info("Running node command: {$cmd}");

        $this->runElevationChartImageGeneration($cmd);

        $localDisk->delete("geojson/$id.geojson");

        if ($ecMediaDisk->exists("ectrack/elevation_charts/$id.svg")) {
            if ($ecMediaDisk->exists("ectrack/elevation_charts/{$id}_old.svg"))
                $ecMediaDisk->delete("ectrack/elevation_charts/{$id}_old.svg");
            $ecMediaDisk->move("ectrack/elevation_charts/$id.svg", "ecmedia/ectrack/elevation_charts/{$id}_old.svg");
        }
        try {
            $ecMediaDisk->writeStream("ectrack/elevation_charts/$id.svg", $localDisk->readStream("elevation_charts/$id.svg"));
        } catch (Exception $e) {
            Log::warning("The elevation chart image could not be written");
            if ($ecMediaDisk->exists("ectrack/elevation_charts/{$id}_old.svg"))
                $ecMediaDisk->move("ectrack/elevation_charts/{$id}_old.svg", "ecmedia/ectrack/elevation_charts/$id.svg");
        }

        if ($ecMediaDisk->exists("ectrack/elevation_charts/{$id}_old.svg"))
            $ecMediaDisk->delete("ectrack/elevation_charts/{$id}_old.svg");

        return $ecMediaDisk->path("ectrack/elevation_charts/{$id}.svg");
    }

    /**
     * Run the effective image generation
     * Imported from geomixer
     *
     * @param string $cmd
     *
     * @throws Exception
     */
    public function runElevationChartImageGeneration(string $cmd): void
    {
        $descriptorSpec = array(
            0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
            2 => array("pipe", "w")    // stderr is a pipe that the child will write to
        );
        flush();

        $process = proc_open($cmd, $descriptorSpec, $pipes, realpath('./'), array());
        if (is_resource($process)) {
            while ($s = fgets($pipes[1])) {
                Log::info($s);
                flush();
            }

            if ($s = fgets($pipes[2]))
                throw new Exception($s);
        }
    }
}
