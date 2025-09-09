<?php

namespace App\Jobs;

use App\Models\App;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildIconsJsonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $app;

    /**
     * Create a new job instance.
     *
     * @param App $app
     * @return void
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info("Starting job for App ID: {$this->app->id}");
            $this->app->buildIconsJson();
            Log::info("Completed job for App ID: {$this->app->id}");
        } catch (\Exception $e) {
            Log::error("Job failed for App ID: {$this->app->id} with error: " . $e->getMessage());
            throw $e; // Rethrow the exception to mark the job as failed
        }
    }
}
