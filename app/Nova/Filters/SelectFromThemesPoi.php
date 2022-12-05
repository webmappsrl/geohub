<?php

namespace App\Nova\Filters;

use App\Models\EcPoi;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class SelectFromThemesPoi extends Filter
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
            return $query->whereHas('taxonomyThemes', function ($q) use ($value) {
                $q->where('id', $value);
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
        $tracks = EcPoi::where('user_id',$request->user()->id)->get();
        $array = [];
        foreach ($tracks as $t) {
            $taxes = $t->taxonomyThemes;
            foreach ($taxes as $t) {
                $array[$t->name] = $t->id;
            }
        }

        return $array;
    }
}
