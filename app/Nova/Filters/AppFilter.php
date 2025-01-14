<?php

namespace App\Nova\Filters;

use App\Models\App;
use Illuminate\Http\Request;
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        // return the models where the sku value contains the digited value
        return $query->where('sku', $value);
    }

    /**
     * Get the filter's available options.
     *
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
            $options[$label] = $app['sku'];
        }

        return $options;
    }
}
