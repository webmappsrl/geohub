<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ImportAndSync extends Command
{
/**
* The name and signature of the console command.
*
* @var string
*/
protected $signature = 'geohub:import_and_sync {import_method}';

/**
* The console command description.
*
* @var string
*/
protected $description = 'Use this command to import data from external resources.';

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
    $method = $this->argument('import_method');
    switch ($method) {

        case 'comuni_italiani':
        $this->comuniItaliani();
        break;

        default:
        $this->error('Invalid method '.$method.'. Available methods: comuni_italiani');
        break;
    }
    return 0;
}

private function comuniItaliani (){

    $this->info('Processing comuni italiani');

//Step 1 : Downlod zip file from url https://www.istat.it/storage/cartografia/confini_amministrativi/non_generalizzati/Limiti01012021.zip

    $tmpzip = tempnam("/tmp/", 'geohub_import_comuni');
    file_put_contents($tmpzip, fopen("https://www.istat.it/storage/cartografia/confini_amministrativi/non_generalizzati/Limiti01012021.zip", 'r'));

    $this->info("$tmpzip created");

//Step 2 : Unzip the file

    $zip = new ZipArchive;
    if ($zip->open($tmpzip) === TRUE) {
        $zip->extractTo('/tmp/geohub_comuni');
        $zip->close();
    } 
    $this->info("file unzipped in /tmp/geohub_comuni");

//Step 3 : Save content in temporary table
    $shape = "/tmp/geohub_comuni/Limiti01012021/Com01012021/Com01012021_WGS84";
    $table = "comuni_".substr(str_shuffle(MD5(microtime())), 0, 5);
    $psql = "PGPASSWORD=".env("DB_PASSWORD")." psql -h ".env("DB_HOST")." -p ".env("DB_PORT")." -d ".env("DB_DATABASE")." -U ".env("DB_USERNAME");
    $command = "shp2pgsql -c  -s 4326  $shape $table | $psql";
    exec($command);
    $this->info("Table $table created");

//Step 4 : Delete file
    $command = "rm $tmpzip";
    exec($command);
    $command = "rm -rf /tmp/geohub_comuni/";
    exec($command);

//Step 5 : Call sync_table method
    $import_method = "comuni_italiani";
    $model_name = "TaxonomyWhere";
    $source_id_field = "pro_com_t";
    $mapping = ['comune' => 'name', 'geom' => 'geometry'];
    $this->syncTable($import_method, $table, $model_name, $source_id_field, $mapping);

//Step 6 : Remove temporary table
    Schema::dropIfExists($table);
    $this->info("Table $table Dropped");
}


public function syncTable($import_method, $tmp_table_name, $model_name, $source_id_field, $mapping){
    /*$this->info("SyncTable : Import Method = $import_method,\n Table name = $tmp_table_name, \n Model Name = $model_name, \n Source id field =  $source_id_field, \n  ");
    $this->info("Mapping :");*/
   
    foreach ($mapping as $src_field => $trg_field)
    {
       /* $this->info("$src_field => $trg_field");*/
    }
}

}
