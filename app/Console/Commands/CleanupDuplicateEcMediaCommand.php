<?php

namespace App\Console\Commands;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\Layer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupDuplicateEcMediaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:cleanup-duplicate-ecmedia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up duplicate EcMedia records, keeping the most recent AWS-hosted media per OutSourceFeature.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting EcMedia duplicate cleanup.');

        $duplicatedOsfEntries = EcMedia::query()
            ->select('out_source_feature_id', DB::raw('COUNT(*) as count_per_osf'))
            ->groupBy('out_source_feature_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->reject(function ($entry) {
                return is_null($entry->out_source_feature_id);
            });

        if ($duplicatedOsfEntries->isEmpty()) {
            $this->info('No EcMedia records found with duplicate out_source_feature_id.');

            return Command::SUCCESS;
        }

        $numberOfOsfIdsWithDuplicates = $duplicatedOsfEntries->count();

        // 2. Calculate total number of excess EcMedia records to be processed/deleted
        $totalExcessEcMediaRecords = 0;
        foreach ($duplicatedOsfEntries as $entry) {
            $totalExcessEcMediaRecords += ($entry->count_per_osf - 1);
        }

        if ($totalExcessEcMediaRecords == 0) {
            $this->info('No excess EcMedia records to process after initial count.');

            return Command::SUCCESS;
        }

        // Detailed Explanation
        $this->newLine();
        $this->line('<fg=cyan>--------------------------------------------------------------------</>');
        $this->line('<fg=cyan> EcMedia Duplicate Cleanup Process</>');
        $this->line('<fg=cyan>--------------------------------------------------------------------</>');
        $this->line('This command will perform the following actions:');
        $this->line("1.  <fg=yellow>Identify Duplicates</>: Finds groups of EcMedia records sharing the same 'out_source_feature_id'.");
        $this->line('2.  <fg=yellow>Select Record to Keep</>: For each group:');
        $this->line("    a. It prioritizes the <options=bold>most recently updated</> EcMedia record whose URL contains 'amazonaws.com'.");
        $this->line('    b. If no AWS-hosted media is found, it keeps the <options=bold>most recently updated</> EcMedia record overall.');
        $this->line('3.  <fg=yellow>Re-map Relationships</>: For each EcMedia record marked for deletion in a group:');
        $this->line("    a. <options=bold>Feature Images</>: Any EcPoi, EcTrack, or Layer records using the duplicate media as a 'feature_image' will be updated to point to the EcMedia record being kept.");
        $this->line('    b. <options=bold>Galleries (ManyToMany)</>: EcPoi and EcTrack records related to the duplicate media (e.g., in image galleries) will be re-associated with the EcMedia record being kept. The old associations will be detached.');
        $this->line('4.  <fg=yellow>Delete Duplicate EcMedia</>: After re-mapping, the duplicate EcMedia records will be deleted from the database.');
        $this->line("    - The EcMedia model's 'deleting' event will trigger, which is expected to handle the deletion of the actual media file from storage (e.g., S3 via HOQU).");
        $this->line('<fg=cyan>--------------------------------------------------------------------</>');
        $this->line('Summary of items to process:');
        $this->line("- Found <options=bold>$numberOfOsfIdsWithDuplicates</> OutSourceFeature IDs with duplicate EcMedia records.");
        $this->line("- A total of <options=bold>$totalExcessEcMediaRecords</> EcMedia records will be processed for deletion.");

        $this->warn('WARNING: This operation WILL MODIFY the database. Ensure you have a backup.');

        if (! $this->confirm('Do you want to proceed with the cleanup?', false)) {
            $this->info('Cleanup aborted by user.');

            return Command::INVALID;
        }
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalExcessEcMediaRecords);
        $progressBar->setFormat("<fg=white;bg=blue> %message:-30s %</>\n <fg=green>%current%</>/<fg=yellow>%max%</> [%bar%] <fg=magenta>%percent:3s%%</>\n <fg=cyan>Time:</> <fg=white>%elapsed:6s%</> <fg=cyan>ETA:</> <fg=white>%remaining:-6s%</> <fg=cyan>Mem:</> <fg=white>%memory:6s%</>");
        $progressBar->setBarCharacter('<fg=green>❚</>');
        $progressBar->setEmptyBarCharacter('<fg=gray>─</>');
        $progressBar->setProgressCharacter('<fg=green;options=bold>➤</>');
        $progressBar->setMessage('Cleaning EcMedia duplicates...');
        $progressBar->start();

        $actualDeletedCount = 0;
        $processedForDeletionLoopCount = 0;

        foreach (
            $duplicatedOsfEntries->pluck('out_source_feature_id')->reject(function ($osfId) {
                return is_null($osfId);
            }) as $osfId
        ) {
            DB::transaction(function () use ($osfId, &$actualDeletedCount, &$processedForDeletionLoopCount, $progressBar) {

                $mediaGroup = EcMedia::where('out_source_feature_id', $osfId)
                    ->orderBy('updated_at', 'desc')
                    ->get();

                if ($mediaGroup->count() <= 1) {
                    return;
                }

                $recordToKeep = null;
                $awsMedia = $mediaGroup->filter(function ($media) {
                    return $media->url && strpos($media->url, 'amazonaws.com') !== false;
                });

                if ($awsMedia->isNotEmpty()) {
                    $recordToKeep = $awsMedia->first();
                } else {
                    $recordToKeep = $mediaGroup->first();
                }

                $recordsToDelete = $mediaGroup->reject(function ($media) use ($recordToKeep) {
                    return $media->id === $recordToKeep->id;
                });

                if ($recordsToDelete->isEmpty()) {
                    return;
                }

                foreach ($recordsToDelete as $mediaToDelete) {
                    $processedForDeletionLoopCount++;

                    EcPoi::where('feature_image', $mediaToDelete->id)->update(['feature_image' => $recordToKeep->id]);
                    EcTrack::where('feature_image', $mediaToDelete->id)->update(['feature_image' => $recordToKeep->id]);
                    Layer::where('feature_image', $mediaToDelete->id)->update(['feature_image' => $recordToKeep->id]);

                    $relatedPoisIds = $mediaToDelete->ecPois()->pluck('ec_pois.id');
                    if ($relatedPoisIds->isNotEmpty()) {
                        // Check which POIs are not already related to the record to keep
                        $existingPoiIds = $recordToKeep->ecPois()->pluck('ec_pois.id');
                        $newPoiIds = $relatedPoisIds->diff($existingPoiIds);

                        if ($newPoiIds->isNotEmpty()) {
                            $recordToKeep->ecPois()->syncWithoutDetaching($newPoiIds);
                        }
                    }
                    $mediaToDelete->ecPois()->detach();

                    $relatedTracksIds = $mediaToDelete->ecTracks()->pluck('ec_tracks.id');
                    if ($relatedTracksIds->isNotEmpty()) {
                        // Check which Tracks are not already related to the record to keep
                        $existingTrackIds = $recordToKeep->ecTracks()->pluck('ec_tracks.id');
                        $newTrackIds = $relatedTracksIds->diff($existingTrackIds);

                        if ($newTrackIds->isNotEmpty()) {
                            $recordToKeep->ecTracks()->syncWithoutDetaching($newTrackIds);
                        }
                    }
                    $mediaToDelete->ecTracks()->detach();

                    try {
                        $mediaToDelete->delete();
                        $actualDeletedCount++;
                    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                        $this->output->newLine(); // Ensure error message is on a new line from progress bar
                        $this->error("Error deleting Media ID {$mediaToDelete->id} (OSF ID: $osfId) due to HttpException: ".$e->getMessage());
                        Log::channel('stderr')->error("    - OSF ID: $osfId - Failed to delete Media ID {$mediaToDelete->id} due to HttpException: ".$e->getMessage());
                    } catch (\Exception $e) {
                        $this->output->newLine(); // Ensure error message is on a new line from progress bar
                        $this->error("Error deleting Media ID {$mediaToDelete->id} (OSF ID: $osfId): ".$e->getMessage());
                        Log::channel('stderr')->error("    - OSF ID: $osfId - Failed to delete Media ID {$mediaToDelete->id}: ".$e->getMessage());
                    }
                }
                $progressBar->advance();
            });
        }

        $progressBar->finish();
        $this->newLine(2);

        $finalMessage = "Cleanup complete. $actualDeletedCount media records were actually deleted (out of $processedForDeletionLoopCount processed).";
        $this->info($finalMessage);

        return Command::SUCCESS;
    }
}
