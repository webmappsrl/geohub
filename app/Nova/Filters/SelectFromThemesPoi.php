<?php

namespace App\Nova\Filters;

use App\Models\TaxonomyTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        if ($value == 'empty') {
            return $query->doesntHave('taxonomyThemes');
        }

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
     * @return array
     */
    public function options(Request $request)
    {
        $current_user_id = $request->user()->id;

        $taxData = DB::select("
        SELECT id,name 
        FROM taxonomy_themes WHERE id IN 
        (SELECT DISTINCT w.id 
        FROM taxonomy_themeables as txt 
        INNER JOIN ec_pois as t on t.id=txt.taxonomy_themeable_id 
        INNER JOIN taxonomy_themes as w on w.id=taxonomy_theme_id 
        WHERE txt.taxonomy_themeable_type='App\Models\EcPoi' 
        AND t.user_id=$current_user_id);");

        $taxModels = TaxonomyTheme::hydrate($taxData)->pluck('id', 'name')->toArray();
        $taxModels['No Theme'] = 'empty';

        return $taxModels;
    }
}
