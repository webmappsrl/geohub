<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ImportAndSync extends Command
{
/**
* The name and signature of the console command.
*
* @var string
*/
protected $signature = 'geohub:import_and_sync 
                        {import_method : Method used to import data. Available: regioni_italiane, province_italiane, comuni_italiani}
                        {--shp= : Path to shape file. Used by some import method.}';

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

        case 'regioni_italiane':
            $this->regioniItaliane();
            break;
        case 'province_italiane':
            $this->provinceItaliane();
            break;
        case 'comuni_italiani':
            $this->comuniItaliani();
            break;

        default:
        $this->error('Invalid method '.$method.'. Available methods: regioni_italiane, province_italiane, comuni_italiani');
        break;
    }
    return 0;
}

    private function regioniItaliane (){

        //Step 1 : CHECK Parameter
        // https://www.istat.it/storage/cartografia/confini_amministrativi/non_generalizzati/Limiti01012021.zip
        $this->info('Processing regioni italiane');
        // SHP FILE is mandatory
        $shape = $this->option('shp');
        if(empty($shape)) {
            $this->error('For this method shp option is mandatory');
            die();
        }

        //Step 2 : Save shape file content in temporary table
        $table=$this->createTemporaryTableFromShape($shape,'32632:4326');
        $this->info("Table $table created");
        // ADD admin_level 4 (subnazionale)
        $this->addAdministrativeLevelToTemporaryTable(4,$table);


        //Step 5 : Call sync_table method
        $import_method = "regioni_italiane";
        $model_name = "TaxonomyWhere";
        $source_id_field = "cod_reg";
        $mapping = ['den_reg' => 'name', 'admin_level' => 'admin_level', 'geom' => 'geometry'];
        $this->syncTable($import_method, $table, $model_name, $source_id_field, $mapping);

        //Step 6 : Remove temporary table
        Schema::dropIfExists($table);
        $this->info("Table $table Dropped");
    }
    private function provinceItaliane (){

        //Step 1 : CHECK Parameter
        // https://www.istat.it/storage/cartografia/confini_amministrativi/non_generalizzati/Limiti01012021.zip
        $this->info('Processing province italiane');
        // SHP FILE is mandatory
        $shape = $this->option('shp');
        if(empty($shape)) {
            $this->error('For this method shp option is mandatory');
            die();
        }

        //Step 2 : Save shape file content in temporary table
        $table=$this->createTemporaryTableFromShape($shape,'32632:4326');
        $this->info("Table $table created");
        // ADD admin_level 6 (subregionale)
        $this->addAdministrativeLevelToTemporaryTable(6,$table);


        //Step 5 : Call sync_table method
        $import_method = "province_italiane";
        $model_name = "TaxonomyWhere";
        $source_id_field = "cod_prov";
        $mapping = ['den_uts' => 'name', 'admin_level' => 'admin_level', 'geom' => 'geometry'];
        $this->syncTable($import_method, $table, $model_name, $source_id_field, $mapping);

        //Step 6 : Remove temporary table
        Schema::dropIfExists($table);
        $this->info("Table $table Dropped");
    }
    private function comuniItaliani (){

        //Step 1 : CHECK Parameter
        // https://www.istat.it/storage/cartografia/confini_amministrativi/non_generalizzati/Limiti01012021.zip
        $this->info('Processing comuni italiani');
        // SHP FILE is mandatory
        $shape = $this->option('shp');
        if(empty($shape)) {
            $this->error('For this method shp option is mandatory');
            die();
        }

        //Step 2 : Save shape file content in temporary table
        $table=$this->createTemporaryTableFromShape($shape,'32632:4326');
        $this->info("Table $table created");
        // ADD admin_level 8 (comune)
        $this->addAdministrativeLevelToTemporaryTable(8,$table);


        //Step 5 : Call sync_table method
        $import_method = "comuni_italiani";
        $model_name = "TaxonomyWhere";
        $source_id_field = "pro_com_t";
        $mapping = ['comune' => 'name', 'admin_level' => 'admin_level', 'geom' => 'geometry'];
        $this->syncTable($import_method, $table, $model_name, $source_id_field, $mapping);

        //Step 6 : Remove temporary table
        Schema::dropIfExists($table);
        $this->info("Table $table Dropped");
    }

    public function syncTable($import_method, $tmp_table_name, $model_name, $source_id_field, $mapping){
        $model_class_name = '\\App\\Models\\'.$model_name;
        //$model = new $model_class_name();
        $offset=0;
        $step=10;
        $new_items = DB::table($tmp_table_name)->offset($offset)->take($step)->get();
        while($new_items->count()>0){
            foreach ($new_items as $new_item) {
                $source_id=$new_item->$source_id_field;
                $item=$model_class_name::where('import_method',$import_method)->
                where('source_id',$source_id)->
                firstOrCreate();
                $item->import_method=$import_method;
                $item->source_id=$source_id;
                foreach($mapping as $k => $v) {
                    $item->$v = $new_item->$k;
                }
                $item->save();
            }
            $offset+=$step;
            $new_items = DB::table($tmp_table_name)->offset($offset)->take($step)->get();
        }
    }

    public function createTemporaryTableFromShape($shape,$srid) {
        $table = 'removeme_'.substr(str_shuffle(MD5(microtime())), 0, 5);
        $psql = '';
        if(!empty(env('DB_PASSWORD'))) {
            $psql.="PGPASSWORD=".env("DB_PASSWORD");
        }
        $psql .= " psql -h ".env("DB_HOST")." -p ".env("DB_PORT")." -d ".env("DB_DATABASE");
        if(!empty(env('DB_USERNAME'))) {
            $psql .= " -U ".env("DB_USERNAME");
        }
        $command = "shp2pgsql -c -s $srid  $shape $table | $psql";
        exec($command);
        return $table;
    }

    /*
     * This method adds column 'admin_level' with $admin_level value to all existing records.
     * If 'admin_level' column is already existing it updates values.
     */
    public function addAdministrativeLevelToTemporaryTable($admin_level,$table) {
        $first = DB::table($table)->first();
        if(!array_key_exists('admin_level',get_object_vars($first))) {
            DB::statement(DB::raw("ALTER TABLE $table ADD COLUMN admin_level integer"));
        }
        // UPDATE
        DB::statement(DB::raw("UPDATE $table SET admin_level=$admin_level"));
    }

}
