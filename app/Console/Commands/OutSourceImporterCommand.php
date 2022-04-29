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

        $tracks = new OutSourceImporterListWP($type,$endpoint);
        $track_list = json_decode($tracks->getList(),true);
        
        foreach ($track_list as $id => $last_modified) {
            $track = new OutSourceImporterFeatureWP($type,$endpoint,$id);
            $track_json = $track->importFeature();
            print_r($track_json);
        }
    }
}
