<?php

namespace App\Nova\Filters;

use App\Models\App;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter; // Importa il modello App se non già fatto

class SchemaFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @var string
     */
    public $name = 'Filter by Form schema';

    protected $type = 'ugc_pois';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        return $query->where(function ($query) use ($value) {
            $query->whereRaw("raw_data->>'id' = ?", [$value])
                ->orWhereRaw("properties->'form'->>'id' = ?", [$value]);
        });
    }

    public function __construct($type = 'ugc_pois')
    {
        $this->type = $type;
    }

    /**
     * Get the filter's available options.
     *
     * @return array
     */
    public function options(Request $request)
    {
        // Raccogli tutte le opzioni di id dal campo poi_acquisition_form di tutte le App
        $options = [];
        if ($request->user()->can('Admin')) {
            $allApps = App::all();
        } else {
            $appIds = $request->user()->apps->pluck('sku')->toArray();
            $allApps = App::whereIn('sku', $appIds)->get();
        }
        foreach ($allApps as $app) {
            $acquisition_form = $this->type == 'ugc_pois' ? $app->poi_acquisition_form : $app->track_acquisition_form;
            $schemas = json_decode($acquisition_form, true);
            foreach ($schemas as $schema) {
                $label = reset($schema['label']);
                $options[$label] = $schema['id'];
            }
        }

        return $options;
    }
}
