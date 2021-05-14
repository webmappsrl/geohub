<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
        $url = $this->argument('url');
        $path = "/public/EcMedia/";
        $file = @file_get_contents($url);
        if ($file === FALSE)
            return $this->error('Error, file does not exists');
        $contents = file_get_contents($url);
        $name = substr($url, strrpos($url, '/') + 1);
        Storage::put($path . substr(str_shuffle(MD5(microtime())), 0, 5), $contents);

        $this->info("File uploaded correctly");
        return 0;
    }
}
