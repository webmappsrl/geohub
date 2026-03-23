<?php

namespace Laravel\Nova\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class ApplyFilter
{
    /**
     * The filter instance.
     *
     * @var Filter
     */
    public $filter;

    /**
     * The value of the filter.
     *
     * @var mixed
     */
    public $value;

    /**
     * Create a new invokable filter applier.
     *
     * @param  Filter  $filter
     * @param  mixed  $value
     * @return void
     */
    public function __construct($filter, $value)
    {
        $this->value = $value;
        $this->filter = $filter;
    }

    /**
     * Apply the filter to the given query.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function __invoke(Request $request, $query)
    {
        $this->filter->apply(
            $request, $query, $this->value
        );

        return $query;
    }
}
