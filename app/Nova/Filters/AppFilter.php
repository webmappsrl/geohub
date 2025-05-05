<?php

namespace App\Nova\Filters;

use App\Models\App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Filters\Filter;

class AppFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    protected $relationName = 'ugc_pois';

    public function setRelation($relationName)
    {
        $this->relationName = $relationName;

        return $this;
    }

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        return $query->where('app_id', $value);
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
            $apps = App::whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from($this->relationName)
                    ->whereRaw('CAST(apps.id AS VARCHAR) = '.$this->relationName.'.app_id');
            })->orderBy('name')->get()->toArray();
        } else {
            $apps = App::where('user_id', $request->user()->id)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from($this->relationName)
                        ->whereRaw('CAST(apps.id AS VARCHAR) = '.$this->relationName.'.app_id');
                })->orderBy('name')->get()->toArray();
        }
        foreach ($apps as $app) {
            $label = $app['name'];
            $options[$label] = $app['id'];
        }

        return $options;
    }
}
