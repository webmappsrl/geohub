<?php

namespace App\Console\Commands;

use App\Jobs\UpdateLayersForAppJob;
use Illuminate\Console\Command;

class UpdateLayersForApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'layers:update {app_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all layers for the given app_id';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Ottieni l'app_id passato come argomento
        $appId = $this->argument('app_id');

        // Dispatch the job
        UpdateLayersForAppJob::dispatch($appId);

        $this->info("Job per l'aggiornamento dei layer per l'app con id $appId dispatchato.");
    }
}
