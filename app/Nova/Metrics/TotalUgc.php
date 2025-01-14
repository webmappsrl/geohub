<?php

namespace App\Nova\Metrics;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;

class TotalUgc extends Value
{
    /**
     * Calculate the value of the metric.
     *
     *
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->result(UgcTrack::get()->count() + UgcPoi::get()->count() + UgcMedia::get()->count());
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [];
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int
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
        return 'total-user-generated-content';
    }
}
