<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Support\Collection;
use Laravel\Nova\FilterDecoder;

trait DecodesFilters
{
    /**
     * Get the filters for the request.
     *
     * @return Collection
     */
    public function filters()
    {
        return (new FilterDecoder($this->filters, $this->availableFilters()))->filters();
    }

    /**
     * Get all of the possibly available filters for the request.
     *
     * @return Collection
     */
    protected function availableFilters()
    {
        return $this->newResource()->availableFilters($this);
    }
}
