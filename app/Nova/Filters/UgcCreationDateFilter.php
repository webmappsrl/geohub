<?php

namespace App\Nova\Filters;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Nova\Filters\DateFilter;

class UgcCreationDateFilter extends DateFilter
{
    public $name = 'Creation Date';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        $value = Carbon::parse($value);

        return $query->where('created_at', '<=', Carbon::parse($value)->endOfDay())->where('created_at', '>=', Carbon::parse($value)->startOfDay());
    }
}
