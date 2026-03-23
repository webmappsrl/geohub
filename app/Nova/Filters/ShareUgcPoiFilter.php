<?php

namespace App\Nova\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\BooleanFilter;

class ShareUgcPoiFilter extends BooleanFilter
{
    public $name = 'Share Ugc Poi';

    /**
     * Apply the filter to the given query.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function apply(Request $request, $query, $value)
    {
        if (! $value['yes']) {
            return $query;
        }
        if ($value['yes']) {
            return $query->whereRaw("properties->'form'->>'share_ugcpoi' = 'yes'");
        }
    }

    /**
     * Get the filter's available options.
     *
     * @return array
     */
    public function options(Request $request)
    {
        return [
            'Yes' => 'yes',
        ];
    }
}
