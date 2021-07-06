<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\Exceptions\CannotStartDump;
use Spatie\DbDumper\Exceptions\DumpFailed;

class DumpDb extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:dump_db';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new sql file exporting all the current database in the local disk under the `database` directory';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        try {
            Storage::disk('local')->makeDirectory('database');
            $dumpFileName = Storage::disk('local')->path('database/dump_' . date('Y-M-d_h-m-s') . '.sql');
            PostgreSql::create()
                ->setDbName(config('database.connections.pgsql.database'))
                ->setUserName(config('database.connections.pgsql.username'))
                ->setPassword(config('database.connections.pgsql.password'))
                ->dumpToFile($dumpFileName);
            Log::info('Database dump created successfully in ' . $dumpFileName);

            return 0;
        } catch (CannotStartDump $e) {
            Log::error('The dump process cannot be initialized: ' . $e->getMessage());
            Log::error('Make sure to clear the config cache when changing the environment: `php artisan config:cache`');

            return 2;
        } catch (DumpFailed $e) {
            Log::error('Error while creating the database dump: ' . $e->getMessage());

            return 1;
        }
    }
}
