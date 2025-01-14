<?php

namespace App\Nova\Filters;

use App\Models\TaxonomyActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Filters\Filter;

class SelectFromActivities extends Filter
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
        if ($value == 'empty') {
            return $query->doesntHave('taxonomyActivities');
        }

        if ($value) {
            return $query->whereHas('taxonomyActivities', function ($q) use ($value) {
                $q->where('id', $value);
            });
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

        $taxData = DB::select("
        SELECT id,name 
        FROM taxonomy_activities WHERE id IN 
        (SELECT DISTINCT w.id 
        FROM taxonomy_activityables as txa 
        INNER JOIN ec_tracks as t on t.id=txa.taxonomy_activityable_id 
        INNER JOIN taxonomy_activities as w on w.id=taxonomy_activity_id 
        WHERE txa.taxonomy_activityable_type='App\Models\EcTrack' 
        AND t.user_id=$current_user_id);");

        $taxModels = TaxonomyActivity::hydrate($taxData)->pluck('id', 'name')->toArray();
        $taxModels['No Activity'] = 'empty';

        return $taxModels;
    }
}
