<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Jobs\WithoutOverlappingBaseJob;

class TestJob extends WithoutOverlappingBaseJob
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('TestJob is executed');
        Redis::set('test_key', 'test_value');
        $value = Redis::get('test_key');
        Log::info('Redis value: ' . $value);

        sleep(10);
    }
}
