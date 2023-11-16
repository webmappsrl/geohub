<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UploadPoiFile extends Action
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
        $file = $fields->file;
        if (!$file) {
            return Action::danger('Please upload a valid file');
        }

        try {
            //import the file
            Excel::import(new \App\Imports\EcPoiFromCSV, $file);
        } catch (\Exception $e) {
            Log::error($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
            return Action::danger($e->getMessage());
        }
        Excel::import(new \App\Imports\EcPoiFromCSV, $file);
        return Action::message('Data imported successfully');
    }


    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        $filePath = public_path('importer-examples/import-poi-example.xlsx');
        $validHeaders = config('services.importers.ecPois.validHeaders');
        $validHeaders = implode(', ', $validHeaders);
        return [
            File::make('Upload File', 'file')
                ->help('<strong> Read the instruction below </strong>' . '</br>' . '</br>' . 'Please upload a valid .xlsx file.' . '</br>' . '<strong>' . 'The file must contain the following headers: ' . $validHeaders . '</strong>' .  '</br>' . '</br>' . 'Mandatory fields are: ' . '<strong>' . ' name_it, poi_type (at least one, referenced by Geohub identifier), theme(at least one, referenced by Geohub identifier), lat, lng.' . '</strong>' . ' Please use comma "," to separate multiple data in a column (eg. 2 different contact_phone).' . '</br>' .  'Please follow this example: ' . '<a href="' . asset('importer-examples/import-poi-example.xlsx') . '" target="_blank">Example</a>')
        ];
    }
}
