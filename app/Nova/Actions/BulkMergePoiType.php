<?php

namespace App\Nova\Actions;

use App\Models\TaxonomyPoiType;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;

class BulkMergePoiType extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Executes the action on a set of selected models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields  Form data fields (e.g., the selected main_poi_type)
     * @param  \Illuminate\Support\Collection  $models  Collection of models (TaxonomyPoiType) selected for the action
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        // Retrieve the ID of the selected Main POI Type from the user input
        $mainId = $fields->get('main_poi_type');

        // Verify if the Main POI Type exists in the database
        $mainRecord = TaxonomyPoiType::find($mainId);
        if (! $mainRecord) {
            return Action::danger("Main POI Type with ID $mainId not found.");
        }

        // Start a transaction to ensure consistency in case of errors
        DB::beginTransaction();

        try {
            // Iterate over each selected POI Type
            foreach ($models as $model) {
                // Update all records associated with the duplicated POI Type to point to the Main POI Type
                DB::table('taxonomy_poi_typeables')
                    ->where('taxonomy_poi_type_id', $model->id)
                    ->update(['taxonomy_poi_type_id' => $mainId]);

                // Delete the duplicated POI Type
                $model->delete();
            }

            // Commit the transaction if all operations were successful
            DB::commit();

            // Return a success message
            return Action::message('Merge completed successfully.');
        } catch (\Exception $e) {
            // Roll back the transaction in case of an error
            DB::rollBack();

            return Action::danger('Error while merging: '.$e->getMessage());
        }
    }

    /**
     * Defines the fields available for the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Select::make('Main POI Type', 'main_poi_type')
                ->options(
                    // Create a list of options for the Select field, including the name and identifier of each POI Type
                    TaxonomyPoiType::all()->mapWithKeys(function ($type) {
                        // Extract the Italian name, or provide a readable fallback if 'it' is missing
                        $name = is_array($type->name) && isset($type->name['it']) ? $type->name['it'] : json_encode($type->name);
                        $identifier = $type->identifier;

                        return [$type->id => "{$name} ({$identifier})"];
                    })->toArray()
                )
                ->displayUsingLabels()
                ->searchable()
                ->rules('required'),
        ];
    }

    /**
     * Defines the name of the action as displayed in the Nova interface.
     *
     * @return string
     */
    public function name()
    {
        return 'Bulk Merge Poi Type';
    }
}
