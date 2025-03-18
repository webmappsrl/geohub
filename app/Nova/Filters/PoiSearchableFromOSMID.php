<?php

namespace App\Nova\Filters;

use App\Models\EcPoi;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class PoiSearchableFromOSMID extends Filter
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        if ($value) {
            return $query->where('id', $value);
        } else {
            return $query;
        }
    }

    /**
     * Get the filter's available options.
     *
     * @return array
     */
    public function options(Request $request)
    {
        $current_user_id = $request->user()->id;

        $taxModels = EcPoi::where('user_id', $current_user_id)->orderBy('osmid')->pluck('id', 'osmid')->toArray();

        return $taxModels;
    }

    public function name()
    {
        return 'OSMID';
    }
}
