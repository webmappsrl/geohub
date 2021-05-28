<?php

namespace App\Console\Commands;

use App\Models\EcMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportEcMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:import_ec_media
                            {url : Url or path of the image to store in the server}
                            {name? : the name of the imported EcMedia}';

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
        $path = 'ec_media/';
        $file = @file_get_contents($url);
        $fileName = basename($this->argument('url'));
        if ($file === FALSE)
            return $this->error('Error, file does not exists');
        $contents = file_get_contents($url);
        
        if ($this->argument('name')) {
            $newEcmedia = EcMedia::create(['name' => $this->argument('name'), 'url' => '']);
            $newEcmedia->url = $path . $newEcmedia->id;
            $newEcmedia->save();
        } else {
            $newEcmedia = EcMedia::create(['name' => $fileName, 'url' => '']);
            $newEcmedia->url = $path . $newEcmedia->id;
            $newEcmedia->save();
        }

        Storage::disk('public')->put('ec_media/' . $newEcmedia->id, $contents);

        $this->info("File uploaded correctly");

        return 0;
    }
}
