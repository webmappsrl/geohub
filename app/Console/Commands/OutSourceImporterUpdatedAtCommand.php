<?php

namespace App\Console\Commands;

use App\Classes\OutSourceImporter\OutSourceImporterFeatureEUMA;
use App\Classes\OutSourceImporter\OutSourceImporterFeatureOSM2CAI;
use App\Classes\OutSourceImporter\OutSourceImporterFeatureOSMPoi;
use App\Classes\OutSourceImporter\OutSourceImporterFeatureSentieriSardegna;
use App\Classes\OutSourceImporter\OutSourceImporterFeatureSICAI;
use App\Classes\OutSourceImporter\OutSourceImporterFeatureSisteco;
use App\Classes\OutSourceImporter\OutSourceImporterFeatureStorageCSV;
use App\Classes\OutSourceImporter\OutSourceImporterFeatureWP;
use App\Classes\OutSourceImporter\OutSourceImporterListEUMA;
use App\Classes\OutSourceImporter\OutSourceImporterListOSM2CAI;
use App\Classes\OutSourceImporter\OutSourceImporterListOSMPoi;
use App\Classes\OutSourceImporter\OutSourceImporterListSentieriSardegna;
use App\Classes\OutSourceImporter\OutSourceImporterListSICAI;
use App\Classes\OutSourceImporter\OutSourceImporterListSisteco;
use App\Classes\OutSourceImporter\OutSourceImporterListStorageCSV;
use App\Classes\OutSourceImporter\OutSourceImporterListWP;
use App\Models\OutSourceFeature;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutSourceImporterUpdatedAtCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:out_source_importer_updated_at 
                              {type : track, poi, media} 
                              {endpoint : url to the resource (e.g. local;importer/parco_maremma/esercizi.csv)} 
                              {provider : WP, StorageCSV, OSM2Cai, sicai} 
                              {--single_feature= : ID of a single feature to import instead of a list (e.g. 1889)} 
                              {--only_related_url : Only imports the related urls to the OSF}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from external source';

    protected $type;

    protected $endpoint;

    protected $single_feature;

    protected $only_related_url;

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
        $this->single_feature = $this->option('single_feature');
        $this->only_related_url = $this->option('only_related_url');

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

            case 'osm2cai':
                return $this->importerOSM2Cai();
                break;

            case 'sicai':
                return $this->importerSICAI();
                break;

            case 'euma':
                return $this->importerEUMA();
                break;

            case 'osmpoi':
                return $this->importerOSMPoi();
                break;

            case 'sentierisardegna':
                return $this->importerSentieriSardegna();
                break;

            case 'sisteco':
                return $this->importerSisteco();
                break;

            default:
                return [];
                break;
        }
    }

    private function importerWP()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListWP($this->type, $this->endpoint);
            $features_list = $features->getList();
        }
        if ($features_list) {
            $count = 1;
            foreach ($features_list as $id => $last_modified) {
                Log::info('Start importing '.$this->type.' number '.$count.' out of '.count($features_list));
                $OSF = new OutSourceImporterFeatureWP($this->type, $this->endpoint, $id, $this->only_related_url);
                $OSF_id = $OSF->importFeature();
                Log::info("OutSourceImporterFeatureWP::importFeature() returns $OSF_id");
                $count++;
            }
        } else {
            Log::info('Importer WP get List is empty.');
        }
    }

    private function importerStorageCSV()
    {
        $features = new OutSourceImporterListStorageCSV($this->type, $this->endpoint);
        $features_list = $features->getList();

        if ($features_list) {
            $count = 1;
            foreach ($features_list as $id => $last_modified) {
                Log::info('Start importing '.$this->type.' number '.$count.' out of '.count($features_list));
                $OSF = new OutSourceImporterFeatureStorageCSV($this->type, $this->endpoint, $id);
                $OSF_id = $OSF->importFeature();
                Log::info("OutSourceImporterFeatureStorageCSV::importFeature() returns $OSF_id");
                $count++;
            }
        } else {
            Log::info('Importer StorageCSV get List is empty.');
        }
    }

    private function importerOSM2Cai()
    {
        $features = new OutSourceImporterListOSM2CAI($this->type, $this->endpoint);
        $features_list = $features->getList();
        if ($features_list) {
            $count = 1;
            if (strpos($this->endpoint, '.txt')) {
                foreach ($features_list as $id => $date) {
                    Log::info('Start importing '.$this->type.' number '.$count.' out of '.count($features_list));
                    $OSF = new OutSourceImporterFeatureOSM2CAI($this->type, $this->endpoint, $id);
                    $OSF_id = $OSF->importFeature();
                    Log::info("OutSourceImporterFeatureOSM2CAI::importFeature() returns $OSF_id");
                    $count++;
                }
            } else {
                foreach ($features_list as $id) {
                    Log::info('Start importing '.$this->type.' number '.$count.' out of '.count($features_list));
                    $OSF = new OutSourceImporterFeatureOSM2CAI($this->type, $this->endpoint, $id);
                    $OSF_id = $OSF->importFeature();
                    Log::info("OutSourceImporterFeatureOSM2CAI::importFeature() returns $OSF_id");
                    $count++;
                }
            }

        } else {
            Log::info('Importer OSM2CAI get List is empty.');
        }
    }

    private function importerSICAI()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListSICAI($this->type, $this->endpoint);
            $features_list = $features->getList();
        }
        if ($features_list) {
            $count = 1;
            foreach ($features_list as $id => $date) {
                Log::info('Start importing '.$this->type.' number '.$count.' out of '.count($features_list));
                $OSF = new OutSourceImporterFeatureSICAI($this->type, $this->endpoint, $id);
                $OSF_id = $OSF->importFeature();
                Log::info("OutSourceImporterFeatureSICAI::importFeature() returns $OSF_id");
                $count++;
            }
        } else {
            Log::info('Importer SICAI get List is empty.');
        }
    }

    private function importerEUMA()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListEUMA($this->type, $this->endpoint);
            $features_list = $features->getList();
        }
        if ($features_list) {
            $all = OutSourceFeature::where('type', $this->type)->where('endpoint', 'LIKE', $this->endpoint)->pluck('updated_at', 'source_id')->toArray();
            $count = 1;
            if ($this->type == 'track') {
                foreach ($features_list as $count => $feature) {
                    if (empty($all) || ! array_key_exists($feature['id'], $all) || $all[$feature['id']] < Carbon::parse($feature['updated_at'])) {
                        Log::info('Start importing '.$this->type.' number '.$count.' out of '.count($features_list));
                        $OSF = new OutSourceImporterFeatureEUMA($this->type, $this->endpoint, $feature['id'], $this->only_related_url);
                        $OSF_id = $OSF->importFeature();
                        Log::info("OutSourceImporterFeatureEUMA::importFeature() returns $OSF_id");
                        $count++;
                    }
                }
            }
            if ($this->type == 'poi') {
                $count = 1;
                foreach ($features_list as $id => $updated_at) {
                    if (empty($all) || ! array_key_exists($id, $all) || $all[$id] < Carbon::parse($updated_at)) {
                        Log::info('Start importing '.$this->type.' number '.$count.' out of '.count($features_list));
                        $OSF = new OutSourceImporterFeatureEUMA($this->type, $this->endpoint, $id, $this->only_related_url);
                        $OSF_id = $OSF->importFeature();
                        Log::info("OutSourceImporterFeatureEUMA::importFeature() returns $OSF_id");
                        $count++;
                    }
                }
            }
        } else {
            Log::info('Importer EUMA get List is empty.');
        }
    }

    private function importerOSMPoi()
    {
        if ($this->type != 'poi') {
            throw new Exception('Only POI type supported by importerOSMPoi');
        }
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListOSMPoi('poi', $this->endpoint);
            $features_list = $features->getList();
        }
        if ($features_list) {
            $count = 1;
            foreach ($features_list as $id => $updated_at) {
                Log::info('Start importing '.$this->type.' number '.$count.' out of '.count($features_list));
                $OSF = new OutSourceImporterFeatureOSMPoi($this->type, $this->endpoint, $id);
                $OSF_id = $OSF->importFeature();
                Log::info("OutSourceImporterFeatureEUMA::importFeature() returns $OSF_id");
                $count++;
            }
        } else {
            Log::info('Importer EUMA get List is empty.');
        }
    }

    private function importerSentieriSardegna()
    {
        $categorie_fruibilita_sentieri = Http::get('https://www.sardegnasentieri.it/ss/tassonomia/categorie_fruibilita_sentieri?_format=json')->json();

        if ($this->single_feature) {
            $source_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListSentieriSardegna($this->type, $this->endpoint);
            $source_list = $features->getList();
        }
        if ($source_list) {
            $osf_list = OutSourceFeature::where('type', $this->type)->where('endpoint', 'LIKE', $this->endpoint)->pluck('updated_at', 'source_id')->toArray();
            $count = 1;
            foreach ($source_list as $id => $updated_at) {
                if (empty($osf_list) || ! array_key_exists($id, $osf_list) || $osf_list[$id] < Carbon::parse($updated_at)) {
                    Log::info('Start importing '.$this->type.' number '.$count);
                    $OSF = new OutSourceImporterFeatureSentieriSardegna($this->type, $this->endpoint, $id, $this->only_related_url, $categorie_fruibilita_sentieri);
                    $OSF_id = $OSF->importFeature();
                    Log::info("OutSourceImporterFeatureSentieriSardegna::importFeature() returns $OSF_id");
                    $count++;
                }
            }
        } else {
            Log::info('Importer SentieriSardegna get List is empty.');
        }
    }

    private function importerSisteco()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListSisteco($this->type, $this->endpoint);
            $features_list = $features->getList();
        }
        if ($features_list) {
            if ($this->type == 'poi') {
                $count = 1;
                foreach ($features_list as $id => $updated_at) {
                    Log::info('Start importing '.$this->type.' number '.$count.' out of '.count($features_list));
                    $OSF = new OutSourceImporterFeatureSisteco($this->type, $this->endpoint, $id, $this->only_related_url);
                    $OSF_id = $OSF->importFeature();
                    Log::info("OutSourceImporterFeatureSisteco::importFeature() returns $OSF_id");
                    $count++;
                }
            }
        } else {
            Log::info('Importer Sisteco get List is empty.');
        }
    }
}
