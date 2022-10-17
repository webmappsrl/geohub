<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\EcTrack;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EcTrackIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:index-tracks {app_id?} {--no-elastic} {--info-elastic} {--jido-elastic}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates ELATIC indexes https://elastic.sis-te.com/geohub_app_{app_id}';

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
        $app_id = $this->argument('app_id');

        if (!isset($app_id)) {
            $this->defaultIndex();
        } else if (isset($app_id)) {
            $this->appIndex($app_id);
        }

        return 0;
    }

    private function appIndex($appId)
    {
        $app = App::find($appId);
        Log::info('===========================');
        Log::info('===========================');
        Log::info('===========================');
        Log::info('Indexing app ' . $app->id);
        if ($this->option('no-elastic')) {
            Log::info('Only config and pois file');
            $app->BuildPoisGeojson();
            $app->BuildConfJson();
        } else if ($this->option('info-elastic')) {
            Log::info('Only info elastic');
            $app->elasticInfoRoutine();
        } else if ($this->option('jido-elastic')) {
            Log::info('Only jido elastic');
            $app->elasticJidoRoutine();
        } else {
            Log::info('Complete index elastic+files');
            $app->elasticRoutine();
        }
        Log::info('===========================');
        Log::info('DONE !!');
        Log::info('===========================');
        Log::info(' ');
    }

    private function defaultIndex()
    {
        $apps = App::all();
        foreach ($apps as $app) {
            $this->appIndex($app->id);
        }
    }
}
