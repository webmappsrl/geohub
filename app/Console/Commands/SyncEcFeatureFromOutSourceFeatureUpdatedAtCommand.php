<?php

namespace App\Console\Commands;

use App\Classes\EcSynchronizer\SyncEcFromOutSource;
use Illuminate\Console\Command;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

class SyncEcFeatureFromOutSourceFeatureUpdatedAtCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:sync-ec-from-out-source-updated-at
                            {type : Set the Ec type (track, poi, media, taxonomy)}
                            {author : Set the author that must be assigned to EcFeature crested, use email or ID }
                            {--app= : Alternative way to set the EcFeature Author. Take the app author and set the same author. Use app ID}
                            {--P|provider= : Set the provider of the Out Source Features}
                            {--E|endpoint= : Set the endpoint of the Out Source Features}
                            {--activity= : Set the identifier of activity taxonomy that must be assigned to EcFeature created}
                            {--theme= : Set the identifier of theme taxonomy that must be assigned to EcFeature created}
                            {--poi_type= : Set the identifier poi_type taxonomy that must be assigned to EcFeature created, the default is poi}
                            {--name_format=name : Set how the command must save the name. Is a string with curly brackets notation to use dynamics tags value}
                            {--single_feature= : ID of a single feature to import instead of a list (e.g. 1889)}
                            {--only_related_url : Only sync the related urls from the OSF}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command creates or updates EcFeatures from OutSourceFeatures based on given parameters';

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
        $type = $this->argument('type');
        $author = $this->argument('author');

        $provider = '';
        $endpoint = '';
        $activity = '';
        $theme = '';
        $poi_type = '';
        $name_format = $this->option('name_format');
        $single_feature = $this->option('single_feature');
        $only_related_url = $this->option('only_related_url');
        $app = 0;

        $providerOption = $this->option('provider');
        $this->logChannel = $this->getLogChannel($providerOption);

        $this->logChannel->info("Starting SyncEcFeatureFromOutSourceFeatureUpdatedAtCommand for provider: {$providerOption}, type: {$type}");

        if ($this->option('provider')) {
            $provider = $this->option('provider');
        }

        if ($this->option('endpoint')) {
            $endpoint = $this->option('endpoint');
        }

        if ($this->option('activity')) {
            $activity = $this->option('activity');
        }

        if ($this->option('theme')) {
            $theme = $this->option('theme');
        }

        if ($this->option('poi_type')) {
            $poi_type = $this->option('poi_type');
        }

        if ($this->option('app')) {
            $app = $this->option('app');
        }

        $SyncEcFromOutSource = new SyncEcFromOutSource($type, $author, $provider, $endpoint, $activity, $poi_type, $name_format, $app, $theme, $only_related_url, $this->logChannel);
        $this->logChannel->info('Start checking parameters... ');
        if ($SyncEcFromOutSource->checkParameters()) {
            $this->logChannel->info('Parameters checked successfully.');
            $this->logChannel->info('Getting List');
            if ($single_feature) {
                $ids_array = $SyncEcFromOutSource->getOSFFromSingleFeature($single_feature);
                $recentIDs = $ids_array;
            } else {
                // get All OSF ids
                $ids_array = $SyncEcFromOutSource->getList();
                // get All OSF ids and updated_at array
                $ids_updated_at_osf = $SyncEcFromOutSource->getOSFListWithUpdatedAt();
                // get All EcFeatures with osf ids and updated_at array
                $ids_updated_at_ec = $SyncEcFromOutSource->getEcFeaturesListWithUpdatedAt($ids_array);

                // Add safety check before calling deleteOldEcFeatures
                if (count($ids_updated_at_osf) == 0 || count($ids_updated_at_ec) == 0) {
                    Log::channel('single')->info('Skipping deleteOldEcFeatures - empty arrays detected: OSF count: '.count($ids_updated_at_osf).', EC count: '.count($ids_updated_at_ec));
                } else {
                    Log::channel('single')->info("Processing deleteOldEcFeatures for type: {$type} - OSF entries: ".count($ids_updated_at_osf).', EC entries: '.count($ids_updated_at_ec));
                    $SyncEcFromOutSource->deleteOldEcFeatures($ids_updated_at_osf, $ids_updated_at_ec, $type);
                }

                $recentIDs = [];

                foreach ($ids_updated_at_osf as $key => $osfDate) {
                    if (array_key_exists($key, $ids_updated_at_ec)) {
                        $ecDate = $ids_updated_at_ec[$key];

                        if ($osfDate > $ecDate) {
                            $recentIDs[] = $key;
                        }
                    } else {
                        $recentIDs[] = $key;
                    }
                }
            }

            if (! empty($recentIDs)) {
                $this->logChannel->info('List acquired successfully.');
                $this->logChannel->info('Start syncronizing ...');
                $loop = $SyncEcFromOutSource->sync($recentIDs);
                $this->logChannel->info(count($loop).' EC features successfully created.');
            } else {
                $this->logChannel->info('No EC features to create or update based on updated_at timestamps.');
            }
        } else {
            $this->logChannel->error('Parameter check failed for SyncEcFromOutSource.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Gets the log channel based on the provider option.
     */
    private function getLogChannel(?string $providerOption): Logger
    {
        if (empty($providerOption)) {
            return $this->logChannel;
        }

        $providerBaseName = strtolower(class_basename($providerOption));

        $channel = config('out_source_logging.sync_provider_channels.'.$providerBaseName);

        if ($channel) {
            return Log::channel($channel);
        }

        $this->logChannel->warning(class_basename($this).": Channel mapping for provider '{$providerOption}' (normalized to '{$providerLower}' or '{$shortProviderKey}') not found in config/importer_logging.php. Using default channel.");

        return $this->logChannel;
    }
}
