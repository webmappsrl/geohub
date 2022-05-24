<?php

namespace App\Console\Commands;

use App\Classes\OutSourceImporter\OutSourceImporterFeatureStorageCSV;
use App\Classes\OutSourceImporter\OutSourceImporterFeatureWP;
use App\Classes\OutSourceImporter\OutSourceImporterListStorageCSV;
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
    protected $signature = 'geohub:out_source_importer {type : track, poi, media} {endpoint : url to the resource (e.g. local;importer/parco_maremma/esercizi.csv)} {provider : WP, StorageCSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from external source';

    protected $type;
    protected $endpoint;

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
        $this->type = $this->argument('type');
        $this->endpoint = $this->argument('endpoint');
        $provider = $this->argument('provider');

        switch (strtolower($provider)) {
            case 'wp':
                return $this->importerWP();
                break;
            
            case 'storagecsv':
                return $this->importerStorageCSV();
                break;
                    
            default:
                return [];
                break;
        }       
    }

    private function importerWP(){
        $features = new OutSourceImporterListWP($this->type,$this->endpoint);
        $features_list = $features->getList();
        
        foreach ($features_list as $id => $last_modified) {
            $OSF = new OutSourceImporterFeatureWP($this->type,$this->endpoint,$id);
            $OSF_id = $OSF->importFeature();
            Log::info("OutSourceImporterFeatureWP::importFeature() returns $OSF_id");
        }
    }
    
    private function importerStorageCSV(){
        $features = new OutSourceImporterListStorageCSV($this->type,$this->endpoint);
        $features_list = $features->getList();
        
        foreach ($features_list as $id => $last_modified) {
            $OSF = new OutSourceImporterFeatureStorageCSV($this->type,$this->endpoint,$id);
            $OSF_id = $OSF->importFeature();
            Log::info("OutSourceImporterFeatureWP::importFeature() returns $OSF_id");
        }
    }
}
