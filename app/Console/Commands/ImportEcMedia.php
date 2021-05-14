<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportEcMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geoghub:import_ec_media 
                            {url : Url or path of the image to store in the server}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to import image from external resources.';

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
        echo $this->argument('url');
        return 0;
    }
}
