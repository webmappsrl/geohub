<?php

namespace App\Nova\Filters;

use App\Models\TaxonomyActivity;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class SelectFromActivities extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        if ($value) {
            return $query->whereHas('taxonomyActivities', function ($q) use ($value) {
                $q->where('identifier', $value);
            });
        } else {
            return $query;
        }
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function options(Request $request)
    {
        $activities = TaxonomyActivity::all()->pluck('identifier','name');

        return $activities;
    }
}
