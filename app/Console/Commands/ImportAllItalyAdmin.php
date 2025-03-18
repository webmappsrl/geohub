<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class ImportAllItalyAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:import_italy_admin
                            {--url= : Url of the .zip file with Regioni, Province, Comuni data from ISTAT}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command downloads ISTAT .zip file and add Regioni,Province and Comuni to TaxonomyWhere';

    private $url = 'https://www.istat.it/storage/cartografia/confini_amministrativi/non_generalizzati/Limiti01012021.zip';

    private $path;

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

        $this->path = base_path().'/geodata/italy_admin';
        if (! file_exists($this->path)) {
            $this->download();
        } else {
            $this->info("$this->path already existing: skipping download. Remove dir if you want to download it again.");
        }

        $this->info('Import and Sync Regioni');
        $args_and_options = [
            'import_method' => 'regioni_italiane',
            '--shp' => 'geodata/italy_admin/Limiti01012021/Reg01012021/Reg01012021_WGS84',
        ];
        $this->call('geohub:import_and_sync', $args_and_options);

        $this->info('Import and Sync Province');
        $args_and_options = [
            'import_method' => 'province_italiane',
            '--shp' => 'geodata/italy_admin/Limiti01012021/ProvCM01012021/ProvCM01012021_WGS84',
        ];
        $this->call('geohub:import_and_sync', $args_and_options);

        $this->info('Import and Sync Comuni');
        $args_and_options = [
            'import_method' => 'comuni_italiani',
            '--shp' => 'geodata/italy_admin/Limiti01012021/Com01012021/Com01012021_WGS84',
        ];
        $this->call('geohub:import_and_sync', $args_and_options);

        return 0;
    }

    private function download()
    {
        $this->info("$this->path does not exist. Start downloading.");
        // Manage options
        if (! empty($this->option('url'))) {
            $this->url = $this->option('url');
        }
        // check geodata dir
        if (! file_exists(base_path().'/geodata')) {
            mkdir(base_path().'/geodata');
        }
        // Download and unzip
        $this->info('Downloading from '.$this->url.' ... be patient!');
        $tmpzip = $this->path.'italy_'.substr(str_shuffle(md5(microtime())), 0, 5).'.zip';
        file_put_contents($tmpzip, fopen($this->url, 'r'));
        $this->info("$tmpzip downloaded");
        $zip = new ZipArchive;
        if ($zip->open($tmpzip) === true) {
            $zip->extractTo($this->path);
            $zip->close();
        }
        $this->info("file unzipped in $this->path");

        unlink($tmpzip);
    }
}
