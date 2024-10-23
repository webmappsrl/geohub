<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use App\Jobs\WithoutOverlappingBaseJob;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TestJob2 extends WithoutOverlappingBaseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        Log::info('TestJob2 is executed');
        Redis::set('test_key_2', 'test_value_2');
        $value = Redis::get('test_key_2');
        Log::info('Redis value_2: ' . $value);

        sleep(10);
    }
}
