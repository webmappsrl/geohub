<?php

namespace App\Nova\Filters;

use App\Models\EcPoi;
use App\Models\TaxonomyPoiType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Filters\Filter;

class SelectFromPoiTypesPoi extends Filter
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
            return $query->whereHas('taxonomyPoiTypes', function ($q) use ($value) {
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
        FROM taxonomy_poi_types WHERE id IN 
        (SELECT DISTINCT w.id 
        FROM taxonomy_poi_typeables as txw 
        INNER JOIN ec_pois as t on t.id=txw.taxonomy_poi_typeable_id 
        INNER JOIN taxonomy_poi_types as w on w.id=taxonomy_poi_type_id 
        WHERE txw.taxonomy_poi_typeable_type='App\Models\EcPoi' 
        AND t.user_id=$current_user_id);");

        $taxModels = TaxonomyPoiType::hydrate($taxData)->pluck('id','name')->toArray();

        return $taxModels;
    }
}
