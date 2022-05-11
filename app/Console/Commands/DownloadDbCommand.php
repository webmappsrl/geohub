<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DownloadDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'download a dump.sql from server';

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
        echo env('APP_name');
        $BACKUP_SERVER =  env('BACKUP_SERVER');
        if (!$BACKUP_SERVER) {
            Log::error('db:download ENV BACKUP_SERVER does not exist');
            throw new Exception("ENV: BACKUP_SERVER does not exist");
        }
        Log::info('db:download start download backup');
        echo ('db:download start download backup');
        $scp_command = "scp $BACKUP_SERVER ./";
        exec($scp_command);

        if (!file_exists(base_path() . '/dump.sql.gz')) {
            Log::error('db:download download dump.sql.gz FAILED');
            throw new Exception("File dump.sql.gz does not exist");
        }
        exec('gunzip dump.sql.gz -f');
        if (!file_exists(base_path() . '/dump.sql')) {
            Log::error('db:download download dump.sql FAILED');
            throw new Exception("File dump.sql does not exist");
        }

        return 0;
    }
}
