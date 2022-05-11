<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        $fileName = "last-dump.sql.gz";
        $lastDumpRemotePath = "geohub/$fileName";
        $localDirectory = "database";
        $localRootPath = "storage/app";
        $lastDumpLocalPath = "$localDirectory/$fileName";
        
        $wmdumps = Storage::disk('wmdumps');
        $local = Storage::disk('local');

        if (!$wmdumps->exists($lastDumpRemotePath)) {
            Log::error('db:download -> ' . $lastDumpRemotePath . ' does not exist');
            throw new Exception('db:download -> ' . $lastDumpRemotePath . ' does not exist');
        }
        Log::info('db:download -> start download backup');
        echo ('db:download -> start download backup');
        $lastDump = $wmdumps->get($lastDumpRemotePath);
        if (!$lastDump) {
            Log::error('db:download -> ' . $lastDumpRemotePath . ' download error');
            throw new Exception('db:download -> ' . $lastDumpRemotePath . ' download error');
        }
        $local->makeDirectory($localDirectory);
        $local->put($lastDumpLocalPath, $lastDump);

        $GzAbsolutePath = base_path() . "/$localRootPath/$lastDumpLocalPath";
        if (!file_exists($GzAbsolutePath)) {
            Log::error('db:download download last-dump.sql.gz FAILED');
            throw new Exception("File dump.sql.gz does not exist");
        }
        exec("gunzip $GzAbsolutePath  -f");
        $AbsolutePath = base_path() . "/$localRootPath/$localDirectory/last-dump.sql";
        if (!file_exists($AbsolutePath)) {
            Log::error('db:download download dump.sql FAILED');
            throw new Exception("File dump.sql does not exist");
        }

        return 0;
    }
}
