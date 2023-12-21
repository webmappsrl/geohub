<?php

namespace App\Nova\Metrics;

use App\Models\UgcMedia;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;

class NewUgcMediaPerDay extends Trend
{
    /**
     * Calculate the value of the metric.
     *
     *
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->countByDays($request, UgcMedia::class);
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            30 => __('30 Days'),
            60 => __('60 Days'),
            90 => __('90 Days'),
        ];
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
    public function uriKey()
    {
        return 'new-ugc-media-per-day';
    }
}
