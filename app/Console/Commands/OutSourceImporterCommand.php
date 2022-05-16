<?php

namespace App\Console\Commands;

use App\Classes\OutSourceImporter\OutSourceImporterFeatureWP;
use Illuminate\Console\Command;
use App\Classes\OutSourceImporter\OutSourceImporterListWP;
use Illuminate\Support\Facades\Log;

class OutSourceImporterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:out_source_importer {type} {endpoint}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from external source';

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
        $type = $this->argument('type');
        $endpoint = $this->argument('endpoint');

        $features = new OutSourceImporterListWP($type,$endpoint);
        $features_list = $features->getList();
        
        foreach ($features_list as $id => $last_modified) {
            $OSF = new OutSourceImporterFeatureWP($type,$endpoint,$id);
            $OSF_id = $OSF->importFeature();
            Log::info("OutSourceImporterFeatureWP::importFeature() returns $OSF_id");
        }
    }
}
