<?php

namespace App\Console\Commands;

use App\Providers\HoquServiceProvider;
use Illuminate\Console\Command;

class HoquStoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:hoqu_store
                            {job : Name of the GEOMIXER job that must be executed}
                            {id : feature ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It sends a single feature hoqu store command.';

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
        $job = $this->argument('job');
        $id = $this->argument('id');
        $this->info("Sending store to HOQU for job $job with ID $id");
        $hoquServiceProvider = app(HoquServiceProvider::class);
        $hoquServiceProvider->store($job, ['id' => $id]);
    }
}
