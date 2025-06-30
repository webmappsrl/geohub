<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

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
        $skipped = 0;

        foreach ($users as $user) {
            $oldEmail = $user->email;
            $newEmail = strtolower($oldEmail);

            $existingUser = User::where('email', $newEmail)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                $this->line("\n   ⚠️  SKIP: {$oldEmail} → {$newEmail} (duplicate with ID: {$existingUser->id})");
                $skipped++;
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
        $this->info("   ⚠️  Skipped (duplicates): {$skipped}");
    }
}
