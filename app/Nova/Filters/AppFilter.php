<?php

namespace App\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Filters\Filter;

class AppFilter extends Filter
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
        //return the models where the app_id value contains the digited value
        return $query->where('app_id', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function options(Request $request)
    {
        $request;


        //if the model is UgcMedia return the app_id values
        if ($request->resource == 'ugc-medias')
            return \App\Models\UgcMedia::select('app_id')->distinct()->get()->pluck('app_id', 'app_id')->toArray();
        //if the model is UgcTrack return the app_id values
        if ($request->resource == 'ugc-tracks')
            return \App\Models\UgcTrack::select('app_id')->distinct()->get()->pluck('app_id', 'app_id')->toArray();
        //if the model is UgcPoint return the app_id values
        if ($request->resource == 'ugc-pois')
            return \App\Models\UgcPoi::select('app_id')->distinct()->get()->pluck('app_id', 'app_id')->toArray();
    }
}
