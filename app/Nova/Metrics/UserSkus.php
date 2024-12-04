<?php

namespace App\Nova\Metrics;

use App\Models\User;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class UserSkus extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param NovaRequest $request
     *
     * @return PartitionResult
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count($request, User::class, 'sku')
            ->label(function ($value) {
                if ($value == null)
                    return 'None';
                else {
                    $str = $value;
                    if (substr($value, 0, 10) == 'it.webmapp') {
                        $array = explode('.', $value);

                        $str = end($array);
                    }

                    return substr($str, 0, 10) . '...';
                }
            });
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey(): string
    {
        return 'user-skus';
    }
}
