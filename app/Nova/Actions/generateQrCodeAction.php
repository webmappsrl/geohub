<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class generateQrCodeAction extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Generate QR Code';

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        // for each model selected call the generateQrCode method
        foreach ($models as $model) {
            $model->generateQrCode($model->qrcode_custom_url);
        }

        // return a success message
        return Action::message('QR Code Generated Successfully!');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
