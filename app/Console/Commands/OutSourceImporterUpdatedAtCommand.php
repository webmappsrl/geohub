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
use App\Mail\SendImportErrorsEmail;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\OutSourceFeature;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

    protected Logger $logChannel;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->logChannel = Log::channel(config('out_source_logging.default_channel', 'stack'));
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

        $this->logChannel->info("Starting OutSourceImporterUpdatedAtCommand for provider: {$provider}, type: {$this->type}, endpoint: {$this->endpoint}".($this->single_feature ? ", single_feature: {$this->single_feature}" : '').($this->only_related_url ? ', only_related_url: true' : ''));

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
                $this->logChannel->error("Provider {$provider} not recognized.");

                return Command::FAILURE; // Or keep return [] and log an error
                break;
        }
    }

    private function importerWP()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            // TODO: Update OutSourceImporterListWP constructor to accept $this->logChannel
            $features = new OutSourceImporterListWP($this->type, $this->endpoint, $this->logChannel);
            $features_list = $features->getList();
        }
        if ($features_list) {
            $count = 1;
            $total = count($features_list);
            $this->logChannel->info("Found {$total} WP items of type {$this->type} to process.");
            foreach ($features_list as $id => $last_modified) {
                $this->logChannel->info("Processing WP {$this->type} {$count}/{$total}, source_id: {$id}");
                // Corrected parameter order: source_id, type, endpoint, only_related_url (optional), logChannel
                $OSF = new OutSourceImporterFeatureWP($id, $this->type, $this->endpoint, $this->only_related_url, $this->logChannel);
                $OSF_id = $OSF->importFeature();
                $this->logChannel->info("OutSourceImporterFeatureWP::importFeature() for source_id {$id} returned OSF_id: ".($OSF_id ?? 'null'));
                $count++;
            }
        } else {
            $this->logChannel->info("Importer WP: No features found or list is empty for type {$this->type} and endpoint {$this->endpoint}.");
        }

        return Command::SUCCESS;
    }

    private function importerStorageCSV()
    {
        // TODO: Update OutSourceImporterListStorageCSV constructor to accept $this->logChannel
        $features = new OutSourceImporterListStorageCSV($this->type, $this->endpoint, $this->logChannel);
        $features_list = $features->getList();

        if ($features_list) {
            $count = 1;
            $total = count($features_list);
            $this->logChannel->info("Found {$total} StorageCSV items of type {$this->type} to process.");
            foreach ($features_list as $id => $last_modified) {
                $this->logChannel->info("Processing StorageCSV {$this->type} {$count}/{$total}, source_id: {$id}");
                // TODO: Update OutSourceImporterFeatureStorageCSV constructor: source_id, type, endpoint, logChannel
                $OSF = new OutSourceImporterFeatureStorageCSV($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                $OSF_id = $OSF->importFeature();
                $this->logChannel->info("OutSourceImporterFeatureStorageCSV::importFeature() for source_id {$id} returned OSF_id: ".($OSF_id ?? 'null'));
                $count++;
            }
        } else {
            $this->logChannel->info("Importer StorageCSV: No features found or list is empty for type {$this->type} and endpoint {$this->endpoint}.");
        }

        return Command::SUCCESS;
    }

    private function importerOSM2Cai()
    {
        // TODO: Update OutSourceImporterListOSM2CAI constructor to accept $this->logChannel
        $features = new OutSourceImporterListOSM2CAI($this->type, $this->endpoint, $this->logChannel);
        $features_list = $features->getList();
        if ($features_list) {
            $count = 1;
            $total = count($features_list);
            $this->logChannel->info("Found {$total} OSM2Cai items of type {$this->type} to process.");
            if (strpos($this->endpoint, '.txt')) {
                foreach ($features_list as $id => $date) {
                    $this->logChannel->info("Processing OSM2Cai {$this->type} {$count}/{$total}, source_id: {$id}");
                    // Corrected parameter order: source_id, type, endpoint, logChannel
                    $OSF = new OutSourceImporterFeatureOSM2CAI($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureOSM2CAI::importFeature() for source_id {$id} returned OSF_id: ".($OSF_id ?? 'null'));
                    $count++;
                }
            } else {
                foreach ($features_list as $id) { // Here $id is the direct source_id
                    $this->logChannel->info("Processing OSM2Cai {$this->type} {$count}/{$total}, source_id: {$id}");
                    // Corrected parameter order: source_id, type, endpoint, logChannel
                    $OSF = new OutSourceImporterFeatureOSM2CAI($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureOSM2CAI::importFeature() for source_id {$id} returned OSF_id: ".($OSF_id ?? 'null'));
                    $count++;
                }
            }
        } else {
            $this->logChannel->info("Importer OSM2Cai: No features found or list is empty for type {$this->type} and endpoint {$this->endpoint}.");
        }

        return Command::SUCCESS;
    }

    private function importerSICAI()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-M-d H:i:s');
        } else {
            // TODO: Update OutSourceImporterListSICAI constructor to accept $this->logChannel
            $features = new OutSourceImporterListSICAI($this->type, $this->endpoint, $this->logChannel);
            $features_list = $features->getList();
        }
        if ($features_list) {
            $count = 1;
            $total = count($features_list);
            $this->logChannel->info("Found {$total} SICAI items of type {$this->type} to process.");
            foreach ($features_list as $id => $date) {
                $this->logChannel->info("Processing SICAI {$this->type} {$count}/{$total}, source_id: {$id}");
                // TODO: Update OutSourceImporterFeatureSICAI constructor: source_id, type, endpoint, logChannel
                $OSF = new OutSourceImporterFeatureSICAI($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                $OSF_id = $OSF->importFeature();
                $this->logChannel->info("OutSourceImporterFeatureSICAI::importFeature() for source_id {$id} returned OSF_id: ".($OSF_id ?? 'null'));
                $count++;
            }
        } else {
            $this->logChannel->info("Importer SICAI: No features found or list is empty for type {$this->type} and endpoint {$this->endpoint}.");
        }

        return Command::SUCCESS;
    }

    private function importerEUMA()
    {
        if ($this->single_feature) {
            $features_list[$this->single_feature] = ['id' => $this->single_feature, 'updated_at' => date('Y-m-d H:i:s')]; // Adjusted for EUMA structure if list returns array of features
            if ($this->type == 'poi') { // EUMA POI list might be id => updated_at
                $features_list = [$this->single_feature => date('Y-m-d H:i:s')];
            }
        } else {
            // TODO: Update OutSourceImporterListEUMA constructor to accept $this->logChannel
            $features = new OutSourceImporterListEUMA($this->type, $this->endpoint, $this->logChannel);
            $features_list = $features->getList();
        }

        if ($features_list) {
            $all = OutSourceFeature::where('type', $this->type)->where('endpoint', 'LIKE', $this->endpoint)->pluck('updated_at', 'source_id')->toArray();
            $item_count_processed = 0;
            $total_items_in_list = count($features_list);
            $this->logChannel->info("Found {$total_items_in_list} EUMA items of type {$this->type} to process. Comparing with existing OSF entries.");

            if ($this->type == 'track') {
                // EUMA track list is typically an array of feature arrays, each having 'id' and 'updated_at'
                foreach ($features_list as $feature_data) {
                    $id = $feature_data['id'];
                    $updated_at = $feature_data['updated_at'];
                    if (empty($all) || ! array_key_exists($id, $all) || Carbon::parse($all[$id]) < Carbon::parse($updated_at)) {
                        $this->logChannel->info("Processing EUMA track, source_id: {$id} (item ".($item_count_processed + 1).')');
                        // Assuming EUMA Feature constructor is: source_id, type, endpoint, only_related_url (optional), logChannel
                        // TODO: Confirm OutSourceImporterFeatureEUMA constructor signature regarding only_related_url
                        $OSF = new OutSourceImporterFeatureEUMA($id, $this->type, $this->endpoint, $this->only_related_url, $this->logChannel);
                        $OSF_id = $OSF->importFeature();
                        $this->logChannel->info("OutSourceImporterFeatureEUMA::importFeature() for source_id {$id} returned OSF_id: ".($OSF_id ?? 'null'));
                        $item_count_processed++;
                    } else {
                        $this->logChannel->debug("Skipping EUMA track, source_id: {$id} (item ".($item_count_processed + 1).') - already up to date or older.');
                    }
                }
            } elseif ($this->type == 'poi') {
                // EUMA POI list is typically id => updated_at
                foreach ($features_list as $id => $updated_at_str) {
                    if (empty($all) || ! array_key_exists($id, $all) || Carbon::parse($all[$id]) < Carbon::parse($updated_at_str)) {
                        $this->logChannel->info("Processing EUMA POI, source_id: {$id} (item ".($item_count_processed + 1).')');
                        // Assuming EUMA Feature constructor is: source_id, type, endpoint, only_related_url (optional), logChannel
                        // TODO: Confirm OutSourceImporterFeatureEUMA constructor signature regarding only_related_url
                        $OSF = new OutSourceImporterFeatureEUMA($id, $this->type, $this->endpoint, $this->only_related_url, $this->logChannel);
                        $OSF_id = $OSF->importFeature();
                        $this->logChannel->info("OutSourceImporterFeatureEUMA::importFeature() for source_id {$id} returned OSF_id: ".($OSF_id ?? 'null'));
                        $item_count_processed++;
                    } else {
                        $this->logChannel->debug("Skipping EUMA POI, source_id: {$id} (item ".($item_count_processed + 1).') - already up to date or older.');
                    }
                }
            }
            $this->logChannel->info("Importer EUMA: Processed {$item_count_processed} items out of {$total_items_in_list} from the source list for type {$this->type}.");
        } else {
            $this->logChannel->info("Importer EUMA: No features found or list is empty for type {$this->type} and endpoint {$this->endpoint}.");
        }

        return Command::SUCCESS;
    }

    private function importerOSMPoi()
    {
        if ($this->type != 'poi') {
            $this->logChannel->error('Only POI type supported by importerOSMPoi, type given: '.$this->type);
            throw new Exception('Only POI type supported by importerOSMPoi');
        }
        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-m-d H:i:s');
        } else {
            // TODO: Update OutSourceImporterListOSMPoi constructor to accept $this->logChannel
            $features = new OutSourceImporterListOSMPoi('poi', $this->endpoint, $this->logChannel);
            try {
                $features_list = $features->getList();
                $this->logChannel->info("Features list content - count: " . count($features_list) . ", data: ", $features_list);
            } catch (Exception $e) {
                $this->logChannel->error('Error during getList of OSMPoi: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        if ($features_list) {
            $osf_list = OutSourceFeature::where('type', $this->type)->where('endpoint', 'LIKE', $this->endpoint)->pluck('updated_at', 'source_id')->toArray();

            $this->logChannel->info('Found '.count($features_list).' OSMPoi items to process. Existing OSF items for this endpoint: '.count($osf_list));

            if (count($osf_list) == 0 || count($features_list) == 0) {
                $this->logChannel->info("Skipping deleteOldEcFeatures processing - empty arrays: OSF count: " . count($osf_list) . ", EC count: " . count($features_list));
                $deleteEntries = [];
            } else {
                // Identify entries to delete from OutSourceFeatures
                $deleteEntries = array_diff(array_keys($osf_list), array_keys($features_list));
            }

            // Delete entries that are not in $features_list
            if (! empty($deleteEntries) && count($features_list) > 0) {
                $this->logChannel->info('Found ' . count($deleteEntries) . ' OSMPoi entries to delete from OutSourceFeatures and related EcPoi.');
                // Delete EcPoi entries where it's OutSourceFeature with relation on out_source_feature_id has the source_id in $deleteEntries
                $deleteEntriesString = array_map(function ($entry) {
                    return "'$entry'";
                }, $deleteEntries);
                $implodeDeleteEntries = implode(',', $deleteEntriesString);

                // Building a raw query to avoid issues with large arrays in whereIn, though Laravel handles it well usually.
                // This part needs careful testing with actual DB structure if $implodeDeleteEntries gets very large.
                $ec_ids_to_delete_query = "SELECT ec_pois.id FROM ec_pois INNER JOIN out_source_features ON ec_pois.out_source_feature_id = out_source_features.id WHERE out_source_features.source_id IN ({$implodeDeleteEntries}) AND out_source_features.type = '{$this->type}' AND out_source_features.endpoint LIKE '{$this->endpoint}'";

                try {
                    $ec_ids_result = DB::select($ec_ids_to_delete_query);
                    $ec_ids = array_column($ec_ids_result, 'id');
                    if (! empty($ec_ids)) {
                        EcPoi::whereIn('id', $ec_ids)->delete();
                        $this->logChannel->info('Deleted '.count($ec_ids).' EcPoi entries linked to outdated OSMPoi features.');
                    }

                    $deletedOsfCount = OutSourceFeature::where('type', $this->type)
                        ->where('endpoint', 'LIKE', $this->endpoint)
                        ->whereIn('source_id', $deleteEntries)
                        ->delete();
                    $this->logChannel->info("Deleted {$deletedOsfCount} outdated OutSourceFeature (OSMPoi) entries.");
                } catch (Exception $e) {
                    $this->logChannel->error('Error during deletion of outdated OSMPoi entries: '.$e->getMessage());
                }
            } else {
                $this->logChannel->info('No OSMPoi entries to delete from OutSourceFeatures.');
            }

            $count = 1;
            $processed_count = 0;
            $total_features = count($features_list);
            foreach ($features_list as $id => $updated_at) {
                if (empty($osf_list) || ! array_key_exists($id, $osf_list) || Carbon::parse($osf_list[$id]) < Carbon::parse($updated_at)) {
                    $this->logChannel->info("Processing OSMPoi {$this->type} {$count}/{$total_features}, source_id: {$id}");
                    // Corrected parameter order: source_id, type, endpoint, logChannel
                    $OSF = new OutSourceImporterFeatureOSMPoi($this->type, $this->endpoint, $id, $this->only_related_url, $this->logChannel);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureOSMPoi::importFeature() for source_id {$id} returned OSF_id: ".($OSF_id ?? 'null'));
                    $processed_count++;
                } else {
                    $this->logChannel->debug("Skipping OSMPoi {$this->type} {$count}/{$total_features}, source_id: {$id} - already up to date or older.");
                }
                $count++;
            }
            $this->logChannel->info("Importer OSMPoi: Processed {$processed_count} items.");
        } else {
            $this->logChannel->info("Importer OSMPoi: No features found or list is empty for type {$this->type} and endpoint {$this->endpoint}.");
            // If features_list is empty, it implies all existing OSF for this endpoint should be deleted
            $osf_to_delete = OutSourceFeature::where('type', $this->type)->where('endpoint', 'LIKE', $this->endpoint)->pluck('id', 'source_id')->toArray();
            if (! empty($osf_to_delete)) {
                $this->logChannel->info('OSMPoi source list is empty. Deleting all '.count($osf_to_delete).' existing OSF entries for this endpoint and their related EcPois.');
                $ec_ids_to_delete_query = 'SELECT ec_pois.id FROM ec_pois INNER JOIN out_source_features ON ec_pois.out_source_feature_id = out_source_features.id WHERE out_source_features.source_id IN ('.implode(',', array_map(function ($sid) {
                    return "'$sid'";
                }, array_keys($osf_to_delete))).") AND out_source_features.type = '{$this->type}' AND out_source_features.endpoint LIKE '{$this->endpoint}'";
                try {
                    $ec_ids_result = DB::select($ec_ids_to_delete_query);
                    $ec_ids = array_column($ec_ids_result, 'id');
                    if (! empty($ec_ids)) {
                        EcPoi::whereIn('id', $ec_ids)->delete();
                        $this->logChannel->info('Deleted '.count($ec_ids).' EcPoi entries.');
                    }
                    OutSourceFeature::whereIn('id', array_values($osf_to_delete))->delete();
                    $this->logChannel->info('Deleted '.count($osf_to_delete).' OutSourceFeature (OSMPoi) entries as source list was empty.');
                } catch (Exception $e) {
                    $this->logChannel->error('Error during cleanup of OSMPoi entries when source list is empty: '.$e->getMessage());
                }
            }
        }

        return Command::SUCCESS;
    }

    private function importerSentieriSardegna()
    {
        // TODO: Update OutSourceImporterFeatureSentieriSardegna constructor for $this->logChannel
        // current call: new OutSourceImporterFeatureSentieriSardegna($this->type, $this->endpoint, $id, $this->only_related_url, $categorie_fruibilita_sentieri);
        // needs to be: new OutSourceImporterFeatureSentieriSardegna($id, $this->type, $this->endpoint, $this->only_related_url, $categorie_fruibilita_sentieri, $this->logChannel);

        $categorie_fruibilita_sentieri_url = 'https://www.sardegnasentieri.it/ss/tassonomia/categorie_fruibilita_sentieri?_format=json';
        $this->logChannel->debug("Fetching 'categorie_fruibilita_sentieri' from {$categorie_fruibilita_sentieri_url}");
        $response = Http::get($categorie_fruibilita_sentieri_url);
        if ($response->failed()) {
            $this->logChannel->error("Failed to fetch 'categorie_fruibilita_sentieri' from {$categorie_fruibilita_sentieri_url}. Status: ".$response->status());
            $categorie_fruibilita_sentieri = [];
        } else {
            $categorie_fruibilita_sentieri = $response->json();
            $this->logChannel->debug("'categorie_fruibilita_sentieri' fetched successfully.");
        }

        if ($this->single_feature) {
            $source_list[$this->single_feature] = date('Y-m-d H:i:s');
        } else {
            // TODO: Update OutSourceImporterListSentieriSardegna constructor to accept $this->logChannel
            $features = new OutSourceImporterListSentieriSardegna($this->type, $this->endpoint, $this->logChannel);
            $source_list = $features->getList();
        }
        if ($source_list) {
            $osf_list = OutSourceFeature::where('type', $this->type)->where('endpoint', 'LIKE', $this->endpoint)->pluck('updated_at', 'source_id')->toArray();
            $this->logChannel->info('Found '.count($source_list).' SentieriSardegna items to process. Existing OSF items for this endpoint: '.count($osf_list));

            // Identify entries to delete from OutSourceFeatures
            $deleteEntries = array_diff(array_keys($osf_list), array_keys($source_list));

            // Delete entries that are not in $features_list
            if (! empty($deleteEntries)) {
                $this->logChannel->info('Found '.count($deleteEntries).' SentieriSardegna entries to delete from OutSourceFeatures and related EcFeatures.');
                $deleteEntriesString = array_map(function ($entry) {
                    return "'$entry'";
                }, $deleteEntries);
                $implodeDeleteEntries = implode(',', $deleteEntriesString);
                $base_delete_query = "SELECT T.id FROM %s AS T INNER JOIN out_source_features ON T.out_source_feature_id = out_source_features.id WHERE out_source_features.source_id IN ({$implodeDeleteEntries}) AND out_source_features.type = '{$this->type}' AND out_source_features.endpoint LIKE '{$this->endpoint}'";

                try {
                    if ($this->type == 'poi') {
                        $ec_ids_result = DB::select(sprintf($base_delete_query, 'ec_pois'));
                        $ec_ids = array_column($ec_ids_result, 'id');
                        if (! empty($ec_ids)) {
                            EcPoi::whereIn('id', $ec_ids)->delete();
                        }
                        $this->logChannel->info('Deleted '.count($ec_ids).' EcPoi entries linked to outdated SentieriSardegna features.');
                    }
                    if ($this->type == 'track') {
                        $ec_ids_result = DB::select(sprintf($base_delete_query, 'ec_tracks'));
                        $ec_ids = array_column($ec_ids_result, 'id');
                        if (! empty($ec_ids)) {
                            EcTrack::whereIn('id', $ec_ids)->delete();
                        }
                        $this->logChannel->info('Deleted '.count($ec_ids).' EcTrack entries linked to outdated SentieriSardegna features.');
                    }

                    $deletedOsfCount = OutSourceFeature::where('type', $this->type)
                        ->where('endpoint', 'LIKE', $this->endpoint)
                        ->whereIn('source_id', $deleteEntries)
                        ->delete();
                    $this->logChannel->info("Deleted {$deletedOsfCount} outdated OutSourceFeature (SentieriSardegna) entries.");
                } catch (Exception $e) {
                    $this->logChannel->error('Error during deletion of outdated SentieriSardegna entries: '.$e->getMessage());
                }
            } else {
                $this->logChannel->info('No SentieriSardegna entries to delete from OutSourceFeatures.');
            }

            $count = 1;
            $processed_count = 0;
            $total_features = count($source_list);
            $errors = [];
            foreach ($source_list as $id => $updated_at) {
                if (empty($osf_list) || ! array_key_exists($id, $osf_list) || Carbon::parse($osf_list[$id]) < Carbon::parse($updated_at)) {
                    $this->logChannel->info("Processing SentieriSardegna {$this->type} {$count}/{$total_features}, source_id: {$id}");
                    $OSF = new OutSourceImporterFeatureSentieriSardegna($this->type, $this->endpoint, $id, $this->only_related_url, $categorie_fruibilita_sentieri, $this->logChannel);
                    $OSF_id_result = $OSF->importFeature();
                    if (is_array($OSF_id_result) && isset($OSF_id_result[0][0]) && $OSF_id_result[0][0] == 'error') {
                        $errors[] = $OSF_id_result[0][1];
                        $this->logChannel->error("Error importing SentieriSardegna source_id {$id}: ".json_encode($OSF_id_result[0][1]));
                    } else {
                        $this->logChannel->info("OutSourceImporterFeatureSentieriSardegna::importFeature() for source_id {$id} returned OSF_id: ".($OSF_id_result ?? 'null'));
                    }
                    $processed_count++;
                } else {
                    $this->logChannel->debug("Skipping SentieriSardegna {$this->type} {$count}/{$total_features}, source_id: {$id} - already up to date or older.");
                }
                $count++;
            }
            $this->logChannel->info("Importer SentieriSardegna: Processed {$processed_count} items.");
            if (! empty($errors)) {
                $this->logChannel->error('SentieriSardegna importer encountered '.count($errors).' errors. Sending email notification.');
                $destinatari = config('services.emails.sardegna_sentieri');
                if ($destinatari) {
                    foreach (explode(',', $destinatari) as $destinatario) {
                        Mail::to(trim($destinatario))->send(new SendImportErrorsEmail($errors, 'Sentieri Sardegna Importer'));
                    }
                    $this->logChannel->info("Error email sent to: {$destinatari}");
                } else {
                    $this->logChannel->warning("Email recipients for 'sardegna_sentieri' not configured in services.emails.");
                }
            }
        } else {
            $this->logChannel->info("Importer SentieriSardegna: No features found or list is empty for type {$this->type} and endpoint {$this->endpoint}.");
            // If source_list is empty, it implies all existing OSF for this endpoint should be deleted
            $osf_to_delete = OutSourceFeature::where('type', $this->type)->where('endpoint', 'LIKE', $this->endpoint)->pluck('id', 'source_id')->toArray();
            if (! empty($osf_to_delete)) {
                $this->logChannel->info('SentieriSardegna source list is empty. Deleting all '.count($osf_to_delete).' existing OSF entries for this endpoint and their related EcFeatures.');
                // Logic for deleting related EcFeatures
                $base_delete_query = 'SELECT T.id FROM %s AS T INNER JOIN out_source_features ON T.out_source_feature_id = out_source_features.id WHERE out_source_features.source_id IN ('.implode(',', array_map(function ($sid) {
                    return "'$sid'";
                }, array_keys($osf_to_delete))).") AND out_source_features.type = '{$this->type}' AND out_source_features.endpoint LIKE '{$this->endpoint}'";
                try {
                    if ($this->type == 'poi') {
                        $ec_ids_result = DB::select(sprintf($base_delete_query, 'ec_pois'));
                        $ec_ids = array_column($ec_ids_result, 'id');
                        if (! empty($ec_ids)) {
                            EcPoi::whereIn('id', $ec_ids)->delete();
                        }
                        $this->logChannel->info('Deleted '.count($ec_ids).' EcPoi entries.');
                    }
                    if ($this->type == 'track') {
                        $ec_ids_result = DB::select(sprintf($base_delete_query, 'ec_tracks'));
                        $ec_ids = array_column($ec_ids_result, 'id');
                        if (! empty($ec_ids)) {
                            EcTrack::whereIn('id', $ec_ids)->delete();
                        }
                        $this->logChannel->info('Deleted '.count($ec_ids).' EcTrack entries.');
                    }
                    OutSourceFeature::whereIn('id', array_values($osf_to_delete))->delete();
                    $this->logChannel->info('Deleted '.count($osf_to_delete).' OutSourceFeature (SentieriSardegna) entries as source list was empty.');
                } catch (Exception $e) {
                    $this->logChannel->error('Error during cleanup of SentieriSardegna entries when source list is empty: '.$e->getMessage());
                }
            }
        }

        return Command::SUCCESS;
    }

    private function importerSisteco()
    {
        // TODO: Update OutSourceImporterFeatureSisteco constructor for $this->logChannel
        // current call: new OutSourceImporterFeatureSisteco($this->type, $this->endpoint, $id, $this->only_related_url);
        // needs to be: new OutSourceImporterFeatureSisteco($id, $this->type, $this->endpoint, $this->only_related_url, $this->logChannel);

        if ($this->single_feature) {
            $features_list[$this->single_feature] = date('Y-m-d H:i:s');
        } else {
            // TODO: Update OutSourceImporterListSisteco constructor to accept $this->logChannel
            $features = new OutSourceImporterListSisteco($this->type, $this->endpoint, $this->logChannel);
            $features_list = $features->getList();
        }
        if ($features_list) {
            $total_features = count($features_list);
            $this->logChannel->info("Found {$total_features} Sisteco items of type {$this->type} to process.");
            if ($this->type == 'poi') { // Sisteco seems to primarily handle POIs based on this logic
                $count = 1;
                $processed_count = 0;
                foreach ($features_list as $id => $updated_at) { // Assuming list provides id => updated_at
                    $this->logChannel->info("Processing Sisteco {$this->type} {$count}/{$total_features}, source_id: {$id}");
                    $OSF = new OutSourceImporterFeatureSisteco($id, $this->type, $this->endpoint, $this->only_related_url, $this->logChannel);
                    $OSF_id = $OSF->importFeature();
                    $this->logChannel->info("OutSourceImporterFeatureSisteco::importFeature() for source_id {$id} returned OSF_id: ".($OSF_id ?? 'null'));
                    $processed_count++;
                    $count++;
                }
                $this->logChannel->info("Importer Sisteco: Processed {$processed_count} POI items.");
            } else {
                $this->logChannel->warning("Importer Sisteco: Type '{$this->type}' may not be fully supported or implemented in this importer method. Current logic primarily handles 'poi'.");
            }
        } else {
            $this->logChannel->info("Importer Sisteco: No features found or list is empty for type {$this->type} and endpoint {$this->endpoint}.");
        }

        return Command::SUCCESS;
    }

    /**
     * Gets the log channel based on the provider.
     */
    private function getLogChannel(string $providerArgument): Logger
    {
        if (empty($providerArgument)) {
            return $this->logChannel;
        }
        $providerLower = strtolower($providerArgument);
        // Attempt to get the specific channel for the provider from config
        $channel = config('out_source_logging.importer_provider_channels.'.$providerLower);

        if ($channel) {
            return Log::channel($channel);
        }

        // If the specific provider channel is not found, log a warning and use the default channel from config
        $this->logChannel->warning(class_basename($this).": Channel mapping for provider '{$providerArgument}' not found in config/importer_logging.php. Using default channel.");

        return $this->logChannel;
    }
}
