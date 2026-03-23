<?php

namespace App\Nova\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\BooleanFilter;

class HasImageGallery extends BooleanFilter
{
    /**
     * Apply the filter to the given query.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function apply(Request $request, $query, $value)
    {
        if (! $value['no']) {
            return $query;
        }
        if ($value['no']) {
            return $query->whereDoesntHave('ecMedia');
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
            'No' => 'no',
        ];
    }
}
