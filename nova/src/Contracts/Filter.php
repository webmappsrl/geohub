<?php

namespace Laravel\Nova\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface Filter
{
    /**
     * Get the key for the filter.
     *
     * @return string
     */
    public function key();

    /**
     * Apply the filter to the given query.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function apply(Request $request, $query, $value);

    /**
     * Determine if the filter should be available for the given request.
     *
     * @return bool
     */
    public function authorizedToSee(Request $request);
}
