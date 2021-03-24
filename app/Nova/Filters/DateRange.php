<?php

namespace App\Nova\Filters;

use Ampeco\Filters\DateRangeFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DateRange extends DateRangeFilter
{
    protected $column;

    public function __construct($column)
    {
        $this->column = $column;
    }

    public function key()
    {
        return 'date_range_' . $this->column;
    }

    /**
     * Apply the filter to the given query.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        $from = Carbon::parse($value[0])->startOfDay();
        $to = Carbon::parse($value[1])->endOfDay();

        return $query->whereBetween($this->column, [$from, $to]);
    }

    /**
     * Get the filter's available options.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function options(Request $request)
    {
        return [];
    }
}
