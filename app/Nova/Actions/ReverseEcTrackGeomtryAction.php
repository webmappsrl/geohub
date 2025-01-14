<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class ReverseEcTrackGeomtryAction extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Reverse Geometry';

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            try {
                if (empty($model->geometry)) {
                    return response()->json(['error' => 'La traccia non ha una geometria definita.'], 404);
                }

                $reversedGeometry = DB::select(" SELECT ST_Reverse('$model->geometry') as geometry")[0]->geometry;

                $model->geometry = $reversedGeometry;
                $model->save();
            } catch (\Exception $e) {
                Log::error('An error occurred during Reverse operation: '.$e->getMessage());
            }
        }
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
