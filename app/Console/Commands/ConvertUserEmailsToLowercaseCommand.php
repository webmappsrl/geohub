<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use App\Models\UgcMedia;
use App\Models\EcTrack;
use App\Models\EcPoi;

class ConvertUserEmailsToLowercaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:convert-user-emails-to-lowercase {--dry-run : Run the command without saving changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert all user emails to lowercase without updating updated_at';

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
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info(' DRY RUN MODE - No changes will be saved');
        }

        $this->convertUserEmails($dryRun);

        $this->info('✅ User email conversion completed!');

        return 0;
    }

    /**
     * Convert user emails to lowercase.
     *
     * @param  bool  $dryRun
     * @return void
     */
    private function convertUserEmails($dryRun)
    {
        $this->info('📧 Converting user emails...');

        $users = User::whereRaw('email != LOWER(email)')->get();

        if ($users->isEmpty()) {
            $this->info('   ✅ All user emails are already lowercase');

            return;
        }

        $this->info("   📊 Found {$users->count()} users with emails to convert");

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $converted = 0;
        $merged = 0;

        foreach ($users as $user) {
            $oldEmail = $user->email;
            $newEmail = strtolower($oldEmail);

            $existingUser = User::where('email', $newEmail)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                // Transfer all data associated with the existing user
                $this->line("\n   🔄 MERGE: {$oldEmail} → {$newEmail} (merged with ID: {$existingUser->id})");
                $this->transferUserData($user, $existingUser, $dryRun);
                $merged++;
            } else {
                if (! $dryRun) {
                    $user->email = $newEmail;
                    $user->timestamps = false;
                    $user->saveQuietly();
                }

                $this->line("\n   🔄 {$oldEmail} → {$newEmail}");
                $converted++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('📊 Summary:');
        $this->info("   ✅ Converted: {$converted}");
        $this->info("   🔄 Merged: {$merged}");
    }

    /**
     * Transfer all user data from one user to another.
     *
     * @param  User  $fromUser
     * @param  User  $toUser
     * @param  bool  $dryRun
     * @return void
     */
    private function transferUserData($fromUser, $toUser, $dryRun)
    {
        if (!$dryRun) {
            // Disable timestamps for all models
            UgcPoi::withoutTimestamps();
            UgcTrack::withoutTimestamps();
            UgcMedia::withoutTimestamps();
            EcTrack::withoutTimestamps();
            EcPoi::withoutTimestamps();
        }

        // Transfer UgcPoi
        $ugcPoisCount = $fromUser->ugc_pois()->count();
        if ($ugcPoisCount > 0) {
            if (!$dryRun) {
                $fromUser->ugc_pois()->updateQuietly(['user_id' => $toUser->id]);
            }
            $this->line("      📊 Transferred {$ugcPoisCount} UgcPoi");
        }

        // Transfer UgcTrack
        $ugcTracksCount = $fromUser->ugc_tracks()->count();
        if ($ugcTracksCount > 0) {
            if (!$dryRun) {
                $fromUser->ugc_tracks()->updateQuietly(['user_id' => $toUser->id]);
            }
            $this->line("      📊 Transferred {$ugcTracksCount} UgcTrack");
        }

        // Transfer UgcMedia
        $ugcMediaCount = $fromUser->ugc_medias()->count();
        if ($ugcMediaCount > 0) {
            if (!$dryRun) {
                $fromUser->ugc_medias()->updateQuietly(['user_id' => $toUser->id]);
            }
            $this->line("      📷 Transferred {$ugcMediaCount} UgcMedia");
        }

        // Transfer EcTrack
        $ecTracksCount = $fromUser->ecTracks()->count();
        if ($ecTracksCount > 0) {
            if (!$dryRun) {
                $fromUser->ecTracks()->updateQuietly(['user_id' => $toUser->id]);
            }
            $this->line("      📊 Transferred {$ecTracksCount} EcTrack");
        }

        // Transfer EcPoi
        $ecPoisCount = $fromUser->ecPois()->count();
        if ($ecPoisCount > 0) {
            if (!$dryRun) {
                $fromUser->ecPois()->updateQuietly(['user_id' => $toUser->id]);
            }
            $this->line("      📊 Transferred {$ecPoisCount} EcPoi");
        }

        // Delete the duplicate user
        if (!$dryRun) {
            $fromUser->delete();
            $this->line("      🗑️  Deleted duplicate user (email: {$fromUser->email}, ID: {$fromUser->id})");
        } else {
            $this->line("      🗑️  Would have deleted duplicate user (email: {$fromUser->email}, ID: {$fromUser->id})");
        }
    }
}
