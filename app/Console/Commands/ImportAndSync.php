<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    private function regioniItaliane()
    {
        //Step 1 : CHECK Parameter
        // https://www.istat.it/storage/cartografia/confini_amministrativi/non_generalizzati/Limiti01012021.zip
        $this->info('Processing regioni italiane');
        // SHP FILE is mandatory
        $shape = $this->option('shp');
        if (empty($shape)) {
            $this->error('For this method shp option is mandatory');
            exit();
        }

        //Step 2 : Save shape file content in temporary table
        $table = $this->createTemporaryTableFromShape($shape, '32632:4326');
        $this->info("Table $table created");
        // ADD admin_level 4 (subnazionale)
        $this->addAdministrativeLevelToTemporaryTable(4, $table);

        //Step 5 : Call sync_table method
        $import_method = 'regioni_italiane';
        $model_name = 'TaxonomyWhere';
        $source_id_field = 'cod_reg';
        $mapping = [
            'name' => 'den_reg',
            'admin_level' => 'admin_level',
            'geometry' => 'geom',
            'identifier' => 'den_reg',
        ];
        $this->syncTable($import_method, $table, $model_name, $source_id_field, $mapping);

        //Step 6 : Remove temporary table
        Schema::dropIfExists($table);
        $this->info("Table $table Dropped");
    }

    private function provinceItaliane()
    {
        //Step 1 : CHECK Parameter
        // https://www.istat.it/storage/cartografia/confini_amministrativi/non_generalizzati/Limiti01012021.zip
        $this->info('Processing province italiane');
        // SHP FILE is mandatory
        $shape = $this->option('shp');
        if (empty($shape)) {
            $this->error('For this method shp option is mandatory');
            exit();
        }

        //Step 2 : Save shape file content in temporary table
        $table = $this->createTemporaryTableFromShape($shape, '32632:4326');
        $this->info("Table $table created");
        // ADD admin_level 6 (subregionale)
        $this->addAdministrativeLevelToTemporaryTable(6, $table);

        //Step 5 : Call sync_table method
        $import_method = 'province_italiane';
        $model_name = 'TaxonomyWhere';
        $source_id_field = 'cod_prov';
        $mapping = [
            'name' => 'den_uts',
            'admin_level' => 'admin_level',
            'geometry' => 'geom',
            'identifier' => 'den_uts',
        ];
        $this->syncTable($import_method, $table, $model_name, $source_id_field, $mapping);

        //Step 6 : Remove temporary table
        Schema::dropIfExists($table);
        $this->info("Table $table Dropped");
    }

    private function comuniItaliani()
    {
        //Step 1 : CHECK Parameter
        // https://www.istat.it/storage/cartografia/confini_amministrativi/non_generalizzati/Limiti01012021.zip
        $this->info('Processing comuni italiani');
        // SHP FILE is mandatory
        $shape = $this->option('shp');
        if (empty($shape)) {
            $this->error('For this method shp option is mandatory');
            exit();
        }

        //Step 2 : Save shape file content in temporary table
        $table = $this->createTemporaryTableFromShape($shape, '32632:4326');
        $this->info("Table $table created");

        // ADD admin_level 8 (comune)
        $this->addAdministrativeLevelToTemporaryTable(8, $table);

        //Step 5 : Call sync_table method
        $import_method = 'comuni_italiani';
        $model_name = 'TaxonomyWhere';
        $source_id_field = 'pro_com_t';
        $mapping = [
            'name' => 'comune',
            'admin_level' => 'admin_level',
            'geometry' => 'geom',
            'identifier' => 'pro_com_t',
        ];
        $this->syncTable($import_method, $table, $model_name, $source_id_field, $mapping);

        //Step 6 : Remove temporary table
        Schema::dropIfExists($table);
        $this->info("Table $table Dropped");
    }

    /**
     * Sync the rows of the temporary table with the given model name.
     *
     * @param  string  $import_method      the method used to import the temporary table rows
     * @param  string  $tmp_table_name     the temporary table name
     * @param  string  $model_name         the model where to import the new rows
     * @param  string  $source_id_field    the id used in the source table. This is used to track the
     *                                   row to let us update it when needed
     * @param  array  $mapping            the mapping to use when import the rows. [`tmp_table_key` => `model_table_key`]
     * @param  string  $defaultLanguage    the default language of the translatable fields
     * @param  array  $translatableFields the list of translatable fields in the destination model
     */
    public function syncTable(string $import_method, string $tmp_table_name, string $model_name, string $source_id_field, array $mapping, string $defaultLanguage = 'it', array $translatableFields = ['name']): void
    {
        $model_class_name = '\\App\\Models\\'.$model_name;
        $newModel = new $model_class_name();
        $tableName = $newModel->table();

        DB::beginTransaction();
        DB::statement("UPDATE $tableName SET import_method = NULL where import_method = '';");
        DB::statement("UPDATE $tableName SET source_id = NULL where source_id = '';");
        DB::statement("
CREATE UNIQUE INDEX import_source_index ON $tableName (import_method, source_id)
WHERE
	import_method IS NOT NULL AND source_id IS NOT NULL;");

        $columnsArray = [
            'import_method',
            'source_id',
            'created_at',
            'updated_at',
        ];
        $valuesArray = [
            "'$import_method'",
            $tmp_table_name.'.'.$source_id_field,
            'NOW()',
            'NOW()',
        ];
        $excludedSetArray = [
            'updated_at = EXCLUDED.updated_at',
        ];

        foreach ($mapping as $k => $v) {
            $columnsArray[] = $k;
            if (in_array($k, $translatableFields)) {
                $valuesArray[] = "'{\"$defaultLanguage\":\"' || ".$tmp_table_name.'.'.$v."|| '\"}'";
            } else {
                $valuesArray[] = $tmp_table_name.'.'.$v;
            }
            $excludedSetArray[] = "$k = EXCLUDED.$k";
        }

        $columnsString = implode(', ', $columnsArray);
        $valuesString = implode(', ', $valuesArray);
        $excludedSetString = implode(', ', $excludedSetArray);

        $query = "INSERT INTO $tableName ($columnsString)
		(SELECT $valuesString FROM $tmp_table_name)
    ON CONFLICT (import_method, source_id)
	WHERE
		import_method IS NOT NULL
		AND source_id IS NOT NULL DO
		UPDATE
		SET $excludedSetString;";

        DB::statement($query);

        DB::statement('DROP INDEX import_source_index;');
        DB::commit();

        $ids = $model_class_name::where('import_method', '=', $import_method)
            ->get('id')
            ->pluck('id');

        foreach ($ids as $id) {
            $model = $model_class_name::find($id);
            usleep(15000);
            $model->save();
        }
    }

    /**
     * Create a temporary table prefixed with `removeme_` containing all the data from the
     * given shapefile
     *
     * @param  string  $shape the shapefile url in a zip format
     * @param  string  $srid  the srid format of the shapefile
     * @return string the name of the created table
     */
    public function createTemporaryTableFromShape(string $shape, string $srid): string
    {
        $table = 'removeme_'.substr(str_shuffle(md5(microtime())), 0, 5);
        $psql = '';
        if (! empty(env('DB_PASSWORD'))) {
            $psql .= 'PGPASSWORD='.config('database.connections.'.config('database.default').'.password');
        }

        $psql .= ' psql -h '.config('database.connections.'.config('database.default').'.host');
        $psql .= ' -p '.config('database.connections.'.config('database.default').'.port');
        $psql .= ' -d '.config('database.connections.'.config('database.default').'.database');
        if (! empty(config('database.connections.'.config('database.default').'.username'))) {
            $psql .= ' -U '.config('database.connections.'.config('database.default').'.username');
        }

        $command = "shp2pgsql -c -s $srid $shape $table | $psql";
        exec($command);

        return $table;
    }

    /**
     * This method adds the 'admin_level' column with $admin_level value to all existing records.
     * If 'admin_level' column is already existing it updates its values.
     *
     * @param  string  $table the table to update
     */
    public function addAdministrativeLevelToTemporaryTable(int $admin_level, string $table)
    {
        $first = DB::table($table)->first();
        if (! array_key_exists('admin_level', get_object_vars($first))) {
            DB::statement(DB::raw("ALTER TABLE $table ADD COLUMN admin_level integer"));
        }
        // UPDATE
        DB::statement(DB::raw("UPDATE $table SET admin_level=$admin_level"));
    }
}
