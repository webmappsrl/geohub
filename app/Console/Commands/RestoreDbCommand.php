<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Restore a last-dump.sql file (must be in root dir)';

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
        Log::info('db:restore -> is started');
        $localDirectory = 'database';
        $localRootPath = 'storage/app';
        $AbsolutePath = base_path()."/$localRootPath/$localDirectory/last-dump.sql";

        if (! file_exists($AbsolutePath)) {
            try {
                Artisan::call('db:download');
            } catch (Exception $e) {
                echo $e;

                return 0;
            }
        }

        $db_name = config('database.connections.pgsql.database');
        $db_user = config('database.connections.pgsql.username');
        $db_password = config('database.connections.pgsql.password');
        $db_host = config('database.connections.pgsql.host');

        $psqlBaseCommand = "PGPASSWORD={$db_password} psql -U {$db_user} -h {$db_host}";

        // psql -c "DROP DATABASE geohub"
        $drop_cmd = $psqlBaseCommand.' -d postgres -c "DROP DATABASE '.$db_name.'"';
        Log::info("db:restore -> $drop_cmd");
        exec($drop_cmd);

        // psql -c "CREATE DATABASE geohub"
        $create_cmd = $psqlBaseCommand.' -d postgres -c "CREATE DATABASE '.$db_name.'"';
        Log::info("db:restore -> $create_cmd");
        exec($create_cmd);

        // psql -d geohub -c "create extension postgis"
        $postgis_cmd = $psqlBaseCommand.' -d '.$db_name.' -c "create extension postgis";';
        Log::info("db:restore -> $postgis_cmd");
        exec($postgis_cmd);

        // psql geohub < last-dump.sql
        $restore_cmd = $psqlBaseCommand." $db_name < $AbsolutePath";
        Log::info("db:restore -> $restore_cmd");
        exec($restore_cmd);

        Log::info('db:restore -> finished');

        return 0;
    }
}
