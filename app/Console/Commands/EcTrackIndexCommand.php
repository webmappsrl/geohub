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
    protected $signature = 'geohub:index-tracks';

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
        $apps = App::all();
        foreach($apps as $app)
        {
            Log::info('===========================');
            Log::info('===========================');
            Log::info('===========================');
            Log:info('Indexing app '.$app->id);
            $app->elasticIndexDelete();
            $app->elasticIndexCreate();
            $app->elasticIndex();
            Log::info('===========================');
            Log::info('DONE !!');
            Log::info('===========================');
            Log::info(' ');
        }
        return 0;
    }
}
