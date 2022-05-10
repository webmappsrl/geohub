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

        $db_name = config('database.connections.pgsql.database');

        // psql -c "DROP DATABASE geohub"
        $drop_cmd = 'psql -c "DROP DATABASE '.$db_name.'"';
        echo $drop_cmd . '\n';
        exec($drop_cmd);
        
        // psql -c "CREATE DATABASE geohub"
        $create_cmd = 'psql -c "CREATE DATABASE '.$db_name.'"';
        echo $create_cmd . '\n';
        exec($create_cmd);

        // psql -d geohub -c "create extension postgis"
        $postgis_cmd = 'psql -d '.$db_name.' -c "create extension postgis";';
        echo $postgis_cmd . '\n';
        exec($postgis_cmd);
        
        // psql geohub < dump.sql
        $restore_cmd = "psql $db_name < dump.sql";

        echo $restore_cmd . '\n';
        exec($restore_cmd);

        return 0;
    }
}