<?php

namespace App\Console\Commands;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HoquMassivePullCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature =
    'geohub:hoqu_massive_pull 
    {model : Name of the model that must be enriched by GEOMIXER (EcTrack, EcPoi, EcMedia)}
    {job : Name of the job that must be used to enrich features by GEOMIXER ()}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It sends (pull) request to HOQU in order to perform a specific job on all features belonging to a specific model';

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
        $model = $this->argument('model');
        $job = $this->argument('job');
        Log::info("HOQU MASSIVE PULL MODEL:$model JOB:$job");

        switch ($model) {
            case 'EcTrack':
                $features = EcTrack::all();
                break;
            case 'EcPoi':
                $features = EcPoi::all();
                break;
            case 'EcMedia':
                $features = EcMedia::all();
                break;

            default:
                Log::info("Model $model not YET supported");

                return 0;
        }
        if ($features->count() > 0) {
            $hoqu = app(HoquServiceProvider::class);
            $count = $features->count();
            Log::info("Found $count features ($model)");
            foreach ($features as $feature) {
                Log::info("Pulling job $job for feature {$feature->id} ($model)");
                $hoqu->store($job, ['id' => $feature->id]);
            }
        }

        return 0;
    }
}
