<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class RestoreDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore a dump.sql file (must be in root dir)';

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

        if(!file_exists(base_path().'/dump.sql')){
            throw new Exception("File dump.sql does not exist");
        }
        #$this->call('db:wipe');

        $db_name = config('database.connections.pgsql.database');

        $drop_cmd = 'psql -c "DROP DATABASE '.$db_name.'"';
        echo $drop_cmd . '\n';
        exec($drop_cmd);
        
        $create_cmd = 'psql -c "CREATE DATABASE '.$db_name.'"';
        echo $create_cmd . '\n';
        exec($create_cmd);

        $postgis_cmd = 'psql -d geohub -c "create extension postgis";';
        echo $postgis_cmd . '\n';
        exec($postgis_cmd);

        // $raster_cmd = 'psql -d geohub -c "create extension raster";';
        // echo $raster_cmd;
        // exec($raster_cmd);

        #$restore_cmd = 'pg_restore -Ft -C -d ' . $db_name . ' < dump.sql';
        $restore_cmd = 'pg_restore -c -F t -d' . $db_name . ' < dump.sql';
        echo $restore_cmd . '\n';
        exec($restore_cmd);

        return 0;
    }
}