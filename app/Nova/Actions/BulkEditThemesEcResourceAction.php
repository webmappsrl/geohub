<?php

namespace App\Nova\Actions;

use App\Models\TaxonomyTheme;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;

class BulkEditThemesEcResourceAction extends Action
{
    use InteractsWithQueue;
    use Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            if (isset($fields['taxonomy_theme'])) {
                $model->taxonomyThemes()->attach($fields['taxonomy_theme']);
            }
            $model->save();
        }
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Select::make('Taxonomy Themes', 'taxonomy_theme')->options(TaxonomyTheme::all()->pluck('name', 'id'))
            ->displayUsingLabels()
            ->searchable()
        ];
    }

    public function name()
    {
        return 'Bulk Assign Theme';
    }
}
