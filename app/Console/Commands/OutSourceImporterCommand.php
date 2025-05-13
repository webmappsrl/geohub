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
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutSourceImporterCommand extends Command
{
    /**
     * for OSM2CAI import the POSGRSQL ports on geohub production server are all closed. In case of need, check if the osm2cai IP isadded to the firewall rules.
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:out_source_importer 
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

    protected $logChannel;

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
        $this->logChannel = $this->getLogChannel($provider);

        $this->logChannel->info("Starting OutSourceImporterCommand for provider: {$provider}, type: {$this->type}, endpoint: {$this->endpoint}");

        switch (strtolower($provider)) {
            case 'wp':
                return $this->importerWP();
            case 'storagecsv':
                return $this->importerStorageCSV();
            case 'osm2cai':
                return $this->importerOSM2Cai();
            case 'sicai':
                return $this->importerSICAI();
            case 'euma':
                return $this->importerEUMA();
            case 'osmpoi':
                return $this->importerOSMPoi();
            case 'sentierisardegna':
                return $this->importerSentieriSardegna();
            case 'sisteco':
                return $this->importerSisteco();
            default:
                $this->logChannel->error("Provider {$provider} not recognized.");
                return Command::FAILURE;
        }
    }

    private function importerWP()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListWP($this->type, $this->endpoint, $this->logChannel);
            $features_list = $features->getList();
        }
        if ($features_list) {
            $count = 1;
            foreach ($features_list as $id => $last_modified) {
                $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                $OSF = new OutSourceImporterFeatureWP($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                $OSF_id = $OSF->importFeature();
                $this->logChannel->info("OutSourceImporterFeatureWP::importFeature() returns $OSF_id");
                $count++;
            }
        } else {
            $this->logChannel->info('Importer WP get List is empty.');
        }
        return Command::SUCCESS;
    }

    private function importerStorageCSV()
    {
        $features = new OutSourceImporterListStorageCSV($this->type, $this->endpoint, $this->logChannel);
        $features_list = $features->getList();

        if ($features_list) {
            $count = 1;
            foreach ($features_list as $id => $last_modified) {
                $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                $OSF = new OutSourceImporterFeatureStorageCSV($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                $OSF_id = $OSF->importFeature();
                $this->logChannel->info("OutSourceImporterFeatureStorageCSV::importFeature() returns $OSF_id");
                $count++;
            }
        } else {
            $this->logChannel->info('Importer StorageCSV get List is empty.');
        }
        return Command::SUCCESS;
    }

    private function importerOSM2Cai()
    {
        $features = new OutSourceImporterListOSM2CAI($this->type, $this->endpoint, $this->logChannel);
        $features_list = $features->getList();
        if ($features_list) {
            $count = 1;
            if (strpos($this->endpoint, '.txt')) {
                foreach ($features_list as $id => $date) {
                    $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                    $OSF = new OutSourceImporterFeatureOSM2CAI($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureOSM2CAI::importFeature() returns $OSF_id");
                    $count++;
                }
            } else {
                foreach ($features_list as $id) {
                    $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                    $OSF = new OutSourceImporterFeatureOSM2CAI($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureOSM2CAI::importFeature() returns $OSF_id");
                    $count++;
                }
            }
        } else {
            $this->logChannel->info('Importer OSM2CAI get List is empty.');
        }
        return Command::SUCCESS;
    }

    private function importerSICAI()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListSICAI($this->type, $this->endpoint, $this->logChannel);
            $features_list = $features->getList();
        }
        if ($features_list) {
            $count = 1;
            foreach ($features_list as $id => $date) {
                $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                $OSF = new OutSourceImporterFeatureSICAI($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                $OSF_id = $OSF->importFeature();
                $this->logChannel->info("OutSourceImporterFeatureSICAI::importFeature() returns $OSF_id");
                $count++;
            }
        } else {
            $this->logChannel->info('Importer SICAI get List is empty.');
        }
        return Command::SUCCESS;
    }

    private function importerEUMA()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListEUMA($this->type, $this->endpoint, $this->logChannel);
            $features_list = $features->getList();
        }
        if ($features_list) {
            if ($this->type == 'track') {
                foreach ($features_list as $count => $feature) {
                    $count++;
                    $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                    $OSF = new OutSourceImporterFeatureEUMA($this->type, $this->endpoint, $feature['id'], $this->only_related_url, $this->logChannel);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureEUMA::importFeature() returns $OSF_id");
                }
            }
            if ($this->type == 'poi') {
                $count = 1;
                foreach ($features_list as $id => $updated_at) {
                    $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                    $OSF = new OutSourceImporterFeatureEUMA($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureEUMA::importFeature() returns $OSF_id");
                    $count++;
                }
            }
        } else {
            $this->logChannel->info('Importer EUMA get List is empty.');
        }
        return Command::SUCCESS;
    }

    private function importerOSMPoi()
    {
        if ($this->type != 'poi') {
            $this->logChannel->error('Only POI type supported by importerOSMPoi');
            throw new Exception('Only POI type supported by importerOSMPoi');
        }
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListOSMPoi('poi', $this->endpoint, $this->logChannel);
            $features_list = $features->getList();
        }
        if ($features_list) {
            $count = 1;
            foreach ($features_list as $id => $updated_at) {
                $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                $OSF = new OutSourceImporterFeatureOSMPoi($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                $OSF_id = $OSF->importFeature();
                $this->logChannel->info("OutSourceImporterFeatureOSMPoi::importFeature() returns $OSF_id");
                $count++;
            }
        } else {
            $this->logChannel->info('Importer OSMPoi get List is empty.');
        }
        return Command::SUCCESS;
    }

    private function importerSentieriSardegna()
    {
        $categorie_fruibilita_sentieri = Http::get('https://www.sardegnasentieri.it/ss/tassonomia/categorie_fruibilita_sentieri?_format=json')->json();

        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListSentieriSardegna($this->type, $this->endpoint, $this->logChannel);
            $features_list = $features->getList();
        }
        if ($features_list) {
            if ($this->type == 'track') {
                $count = 1;
                foreach ($features_list as $id => $feature) {
                    $count++;
                    $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                    $OSF = new OutSourceImporterFeatureSentieriSardegna($this->type, $this->endpoint, $id, $this->only_related_url, $categorie_fruibilita_sentieri);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureSentieriSardegna::importFeature() returns $OSF_id");
                }
            }
            if ($this->type == 'poi') {
                $count = 1;
                foreach ($features_list as $id => $updated_at) {
                    $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                    $OSF = new OutSourceImporterFeatureSentieriSardegna($this->type, $this->endpoint, $id, $this->only_related_url, []);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureSentieriSardegna::importFeature() returns $OSF_id");
                    $count++;
                }
            }
        } else {
            $this->logChannel->info('Importer SentieriSardegna get List is empty.');
        }
        return Command::SUCCESS;
    }

    private function importerSisteco()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            $features = new OutSourceImporterListSisteco($this->type, $this->endpoint, $this->logChannel);
            $features_list = $features->getList();
        }
        if ($features_list) {
            if ($this->type == 'poi') {
                $count = 1;
                foreach ($features_list as $id => $updated_at) {
                    $this->logChannel->info('Start importing ' . $this->type . ' number ' . $count . ' out of ' . count($features_list));
                    $OSF = new OutSourceImporterFeatureSisteco($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureSisteco::importFeature() returns $OSF_id");
                    $count++;
                }
            }
        } else {
            $this->logChannel->info('Importer Sisteco get List is empty.');
        }
        return Command::SUCCESS;
    }

    /**
     * Gets the log channel based on the provider.
     *
     * @param string $provider
     * @return \Illuminate\Log\Logger
     */
    private function getLogChannel(string $provider): \Illuminate\Log\Logger
    {
        $providerLower = strtolower($provider);
        // Use the configuration to get the channel name, with a fallback to the default channel,
        // and a final fallback to 'stack' if the default is not set in the config.
        $channelName = config('out_source_logging.importer_provider_channels.' . $providerLower, config('out_source_logging.default_channel', 'stack'));
        return Log::channel($channelName);
    }
}
