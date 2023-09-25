<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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

        try {
            //import the file
            Excel::import(new \App\Imports\EcTrackFromCSV, $file);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return Action::danger('Error importing file');
        }
        Excel::import(new \App\Imports\EcTrackFromCSV, $file);
        return Action::message('File imported successfully');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [File::make('Upload File', 'file')];
    }
}
