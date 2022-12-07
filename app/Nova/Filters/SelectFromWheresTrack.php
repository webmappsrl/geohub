<?php

namespace App\Nova\Filters;

use App\Models\EcTrack;
use App\Models\TaxonomyWhere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Filters\Filter;

class SelectFromWheresTrack extends Filter
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
            return $query->whereHas('taxonomyWheres', function ($q) use ($value) {
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
        $current_user_id = $request->user()->id;

        $taxData = DB::select("
        SELECT id,name 
        FROM taxonomy_wheres WHERE id IN 
        (SELECT DISTINCT w.id 
        FROM taxonomy_whereables as txw 
        INNER JOIN ec_tracks as t on t.id=txw.taxonomy_whereable_id 
        INNER JOIN taxonomy_wheres as w on w.id=taxonomy_where_id 
        WHERE txw.taxonomy_whereable_type='App\Models\EcTrack' 
        AND t.user_id=$current_user_id);");

        $taxModels = TaxonomyWhere::hydrate($taxData)->pluck('id','name')->toArray();

        return $taxModels;
    }
}
