<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\Exceptions\CannotStartDump;
use Spatie\DbDumper\Exceptions\DumpFailed;

class DumpDb extends Command
{
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
        try {
            Log::info('geohub:dump_db -> is started');
            $wmdumps = Storage::disk('wmdumps');
            $local = Storage::disk('local');
            $local->makeDirectory('database');
            $dumpName = 'dump_'.date('Y-M-d_h-m-s').'.sql';
            $dumpFileName = $local->path('database/'.$dumpName);
            PostgreSql::create()
                ->setDbName(config('database.connections.pgsql.database'))
                ->setUserName(config('database.connections.pgsql.username'))
                ->setPassword(config('database.connections.pgsql.password'))
                ->dumpToFile($dumpFileName);
            Log::info('geohub:dump_db -> Database dump created successfully in '.$dumpFileName);
            if (! $local->exists('database/'.$dumpName)) {
            }
            exec("gzip $dumpFileName  -f");
            $lastLocalDump = $local->get('database/'.$dumpName.'.gz');
            $local->delete('database/'.$dumpName.'.gz');

            Log::info('geohub:dump_db -> START upload to aws');
            $wmdumps->put('geohub/'.$dumpName.'.gz', $lastLocalDump);
            Log::info('geohub:dump_db -> DONE upload to aws');
            //TODO: CREATE LAST DUMP ON REMOTE
            Log::info('geohub:dump_db -> START create last-dump to aws');
            $wmdumps->put('geohub/last-dump.sql.gz', $lastLocalDump);
            Log::info('geohub:dump_db -> DONE create last-dump to aws');

            Log::info('geohub:dump_db -> finished');

            return 0;
        } catch (CannotStartDump $e) {
            Log::error('geohub:dump_db -> The dump process cannot be initialized: '.$e->getMessage());
            Log::error('geohub:dump_db -> Make sure to clear the config cache when changing the environment: `php artisan config:cache`');

            return 2;
        } catch (DumpFailed $e) {
            Log::error('geohub:dump_db -> Error while creating the database dump: '.$e->getMessage());

            return 1;
        }
    }
}
