<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

abstract class WithoutOverlappingBaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Specify the job middleware.
     *
     * @return array
     */
    public function middleware()
    {
        $lockKey = $this->getLockKey();
        Log::info('lockKey: ' . $lockKey);
        return [new WithoutOverlapping($lockKey)];
    }

    /**
     * Get the lock key that will be used to prevent overlapping jobs.
     *
     * @return string
     */
    protected function getLockKey()
    {
        $serializable = $this->getSerializableProperties();
        $lockKey = 'job_lock:' . static::class . ':' . md5(serialize($serializable));
        return $lockKey;
    }

    /**
     * Get the properties of the job that can be serialized.
     *
     * @return array
     */
    protected function getSerializableProperties()
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

        $serializable = [];
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);
            if (is_scalar($value) || is_array($value) || is_null($value)) {
                $serializable[$property->getName()] = $value;
            }
        }

        return $serializable;
    }

    /**
     * Handle the job.
     *
     * @return void
     */
    abstract public function handle();
}
