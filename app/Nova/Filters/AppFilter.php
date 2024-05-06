<?php

namespace App\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;
use \App\Models\App;

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
        $apps = [];
        $options = [];
        if ($request->user()->can('Admin')) {
            $apps = App::all()->toArray();
        } else {
            $apps = App::where('user_id', $request->user()->id)->get()->toArray();
        }
        foreach ($apps as $app) {
            $label = $app['name'];
            $options[$label] = $app['app_id'];
        }

        return $options;
    }
}
