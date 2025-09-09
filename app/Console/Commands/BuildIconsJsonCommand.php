<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\App;
use App\Jobs\BuildIconsJsonJob;

class BuildIconsJsonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:build-app-icons-json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build icons JSON for each App instance';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $apps = App::all();

        foreach ($apps as $app) {
            BuildIconsJsonJob::dispatch($app);
            $this->info("Dispatched job for App ID: {$app->id}");
        }

        $this->info('All jobs have been dispatched successfully.');

        return Command::SUCCESS;
    }
}
