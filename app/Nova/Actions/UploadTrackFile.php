<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\File;
use Maatwebsite\Excel\Facades\Excel;

class UploadTrackFile extends Action
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
        //get the file from the request
        $file = $fields->file;
        if (!$file) {
            return Action::danger('Please upload a valid file');
        }

        try {
            //import the file
            Excel::import(new \App\Imports\EcTrackFromCSV, $file);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return Action::danger($e->getMessage());
        }
        Excel::import(new \App\Imports\EcTrackFromCSV, $file);
        return Action::message('Data imported successfully');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {

        $filePath = Storage::url('importer-examples/import-track-example.xlsx');
        return [
            File::make('Upload File', 'file')
                ->help('<strong> Read the instruction below </strong>' . '</br>' . '</br>' . 'Please upload a valid .xlsx file.' . '</br>' . '<strong>' . 'The file must contain the following headers: ' . '</strong>' . 'id, from, to, ele_from, ele_to, distance, duration_forward, duration_backward, ascent, descent, ele_min, ele_max' . '</br>' . '</br>' . 'Please follow this example: ' . '<a href="' . asset('importer-examples/import-track-example.xlsx') . '" target="_blank">Example</a>')
        ];
    }
}
