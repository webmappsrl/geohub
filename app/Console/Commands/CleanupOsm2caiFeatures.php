<?php

namespace App\Console\Commands;

use App\Models\EcTrack;
use App\Models\OutSourceFeature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupOsm2caiFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:clean-osm2cai-features
                            {--dry-run : Only list features to be deleted, do not execute deletion}
                            {--force : Force the deletion of orphaned features without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes OutSourceFeatures (and related EcTracks) linked to OSM2CAI provider whose source_id does not match a valid id or osmfeatures_id in the osm2cai database hiking_routes table. Requires --force to execute deletion.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');
        $providerName = 'App\Classes\OutSourceImporter\OutSourceImporterFeatureOSM2CAI';

        $this->info('Starting OSM2CAI feature cleanup...');
        if ($isDryRun) {
            $this->warn('Dry Run mode enabled. No data will be deleted.');
        }

        // --- Step 1: Get all valid IDs (id and osmfeatures_id) from osm2cai DB ---
        $this->info('Fetching valid IDs (id and osmfeatures_id) from osm2cai database...');
        $validHikingRouteIdsLookup = [];
        $validOsmFeaturesIdsLookup = [];
        try {
            $hikingRoutesData = DB::connection('out_source_osm')
                ->table('hiking_routes')
                ->select('id', 'osmfeatures_id')
                ->get();

            if ($hikingRoutesData->isEmpty()) {
                $this->error('Could not fetch valid IDs from osm2cai database or the table is empty. Aborting.');
                return 1;
            }

            // Create lookup arrays 
            $validHikingRouteIdsLookup = array_flip($hikingRoutesData->pluck('id')->filter()->all());
            $validOsmFeaturesIdsLookup = array_flip($hikingRoutesData->pluck('osmfeatures_id')->filter()->all());

            $this->info('Found ' . count($validHikingRouteIdsLookup) . ' unique valid hiking route IDs and ' . count($validOsmFeaturesIdsLookup) . ' unique valid osmfeatures_ids.');
        } catch (\Exception $e) {
            Log::error("Error connecting to or querying osm2cai database: {$e->getMessage()}");
            $this->error("Error connecting to or querying osm2cai database: {$e->getMessage()}");
            return 1;
        }

        // --- Step 2: Find potentially orphaned OutSourceFeatures in Geohub ---
        $this->info("Fetching OutSourceFeatures with provider '{$providerName}'coming from api endpoint");
        $orphanedOsfIds = [];
        $skippedOsfCountInvalidJson = 0;
        $skippedOsfCountMissingSourceId = 0; // Counter for missing source_id
        $checkedCount = 0;
        $totalToCheck = OutSourceFeature::where('provider', $providerName)
            ->where('endpoint', 'LIKE', '%https://osm2cai.cai.it/api/v1/hiking-routes/region%') // Filter by api endpoint
            ->count();

        if ($totalToCheck === 0) {
            $this->info("No OutSourceFeatures found for provider '{$providerName}'. Nothing to do.");
            return 0;
        }

        $progressBar = $this->output->createProgressBar($totalToCheck);
        $progressBar->start();

        // Use chunking to avoid memory issues with large datasets
        OutSourceFeature::where('provider', $providerName)
            ->where('endpoint', 'LIKE', '%https://osm2cai.cai.it/api/v1/hiking-routes/region%') // Filter by api endpoint
            ->select('id', 'source_id', 'raw_data') // Select id, source_id, and raw_data
            ->chunkById(500, function ($outSourceFeatures) use (&$orphanedOsfIds, &$skippedOsfCountInvalidJson, &$skippedOsfCountMissingSourceId, $validHikingRouteIdsLookup, $validOsmFeaturesIdsLookup, &$checkedCount, $progressBar) {
                foreach ($outSourceFeatures as $osf) {
                    $checkedCount++;
                    $progressBar->advance();

                    // Check if source_id exists
                    $sourceId = $osf->source_id;
                    if (empty($sourceId)) {
                        Log::warning("Skipping OutSourceFeature ID {$osf->id}: Missing or empty source_id.");
                        $skippedOsfCountMissingSourceId++;
                        continue;
                    }

                    $isOrphan = false;
                    // Perform matching based on source_id format
                    if (strpos((string)$sourceId, 'R') === 0) {
                        // Source ID starts with 'R', check against osmfeatures_id
                        if (!isset($validOsmFeaturesIdsLookup[$sourceId])) {
                            $isOrphan = true;
                        }
                    } else {
                        // Source ID does not start with 'R', check against id
                        // Attempt conversion to integer if it looks like one, as hiking_route.id is likely integer
                        $idToCheck = $sourceId;
                        if (is_numeric($idToCheck)) {
                            $idToCheck = (int)$idToCheck;
                        }
                        if (!isset($validHikingRouteIdsLookup[$idToCheck])) {
                            $isOrphan = true;
                        }
                    }

                    // 4. If checks failed, mark as orphan
                    if ($isOrphan) {
                        $orphanedOsfIds[] = $osf->id; // Store the OutSourceFeature's own ID
                    }
                }
            });

        $progressBar->finish();
        $this->newLine(); // Add a newline after the progress bar

        $this->info("Checked {$checkedCount} OutSourceFeatures.");
        if ($skippedOsfCountInvalidJson > 0) {
            $this->warn("Skipped {$skippedOsfCountInvalidJson} OutSourceFeatures due to invalid JSON in raw_data (check log for details).");
        }
        if ($skippedOsfCountMissingSourceId > 0) {
            $this->warn("Skipped {$skippedOsfCountMissingSourceId} OutSourceFeatures due to missing or empty source_id (check log for details).");
        }

        if (empty($orphanedOsfIds)) {
            $this->info('No orphaned OutSourceFeatures found based on source_id matching.');
            return 0;
        }

        $this->warn('Found ' . count($orphanedOsfIds) . ' potentially orphaned OutSourceFeatures (based on source_id matching). ');

        // --- Step 3: Find related EcTracks ---
        $this->info('Finding related EcTracks for orphaned OutSourceFeatures...');
        $orphanedEcTrackIds = EcTrack::whereIn('out_source_feature_id', $orphanedOsfIds)
            ->pluck('id')
            ->toArray();

        if (!empty($orphanedEcTrackIds)) {
            $this->warn('Found ' . count($orphanedEcTrackIds) . ' related EcTracks to be deleted.');
        } else {
            $this->info('No related EcTracks found for the orphaned OutSourceFeatures.');
        }

        // --- Step 4: Perform Deletion or Dry Run ---
        $isForce = $this->option('force');

        // Define count variables
        $orphanedOsfIdsCount = count($orphanedOsfIds);
        $orphanedEcTrackIdsCount = count($orphanedEcTrackIds);

        if ($isDryRun || !$isForce) {
            // Perform dry run if --dry-run is set OR if --force is NOT set
            $this->info("Dry run: Found {$orphanedOsfIdsCount} orphaned OutSourceFeatures that would be deleted.");
            if ($orphanedEcTrackIdsCount > 0) {
                $this->info("Dry run: Found {$orphanedEcTrackIdsCount} related EcTracks that would be deleted.");
            }
            if (!$isDryRun && !$isForce) { // Add specific message if neither option was used
                $this->warn('Execution halted. Use the --force option to perform the actual deletion.');
            } else {
                $this->info('Dry run finished. No data was deleted.');
            }
        } elseif ($isForce) { // Only delete if --force is explicitly set
            $this->warn("Executing deletion as --force option was specified...");

            // Delete EcTracks first (if any)
            if (!empty($orphanedEcTrackIds)) {
                $this->info('Deleting related EcTracks...');
                $deletedEcTracksCount = EcTrack::destroy($orphanedEcTrackIds);
                $this->info("Deleted {$deletedEcTracksCount} EcTracks.");
            } else {
                $this->info('No EcTracks to delete.');
            }

            // Delete OutSourceFeatures
            $this->info('Deleting orphaned OutSourceFeatures...');
            $deletedOsfCount = OutSourceFeature::destroy($orphanedOsfIds);
            $this->info("Deleted {$deletedOsfCount} OutSourceFeatures.");

            $this->info('Cleanup finished successfully.');
        }

        return 0;
    }
}
