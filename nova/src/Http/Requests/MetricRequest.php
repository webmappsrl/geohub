<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Support\Collection;
use Laravel\Nova\Metrics\Metric;

class MetricRequest extends NovaRequest
{
    /**
     * Get the metric instance for the given request.
     *
     * @return Metric
     */
    public function metric()
    {
        return $this->availableMetrics()->first(function ($metric) {
            return $this->metric === $metric->uriKey();
        }) ?: abort(404);
    }

    /**
     * Get all of the possible metrics for the request.
     *
     * @return Collection
     */
    public function availableMetrics()
    {
        return $this->newResource()->availableCards($this)
            ->whereInstanceOf(Metric::class);
    }
}
