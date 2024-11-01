<?php

namespace App\Nova\Actions;

use App\Models\TaxonomyPoiType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Illuminate\Support\Facades\DB;

class BulkMergePoiType extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        // Ottieni il mainId selezionato dall'utente
        $mainId = $fields->get('main_poi_type');

        // Recupera il record principale
        $mainRecord = TaxonomyPoiType::find($mainId);
        if (!$mainRecord) {
            return Action::danger("Main POI Type con ID $mainId non trovato.");
        }

        DB::beginTransaction();

        try {
            foreach ($models as $duplicate) {
                // Aggiorna tutte le relazioni di taxonomy_poi_typeables per puntare al mainId
                DB::table('taxonomy_poi_typeables')
                    ->where('taxonomy_poi_type_id', $duplicate->id)
                    ->update(['taxonomy_poi_type_id' => $mainId]);

                // Elimina il record duplicato
                $duplicate->delete();
            }

            DB::commit();
            return Action::message("Unione completata con successo.");
        } catch (\Exception $e) {
            DB::rollBack();
            return Action::danger("Errore durante l'unione: " . $e->getMessage());
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
            Select::make('Main POI Type', 'main_poi_type')
                ->options(TaxonomyPoiType::all()->pluck('name', 'id'))
                ->displayUsingLabels()
                ->searchable()
                ->rules('required'),
        ];
    }

    public function name()
    {
        return 'Bulk Merge Poi Type';
    }
}
