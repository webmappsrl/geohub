<?php

namespace App\Nova\Actions;

use App\Models\EcTrack;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\Textarea;
use App\Traits\HandlesData;

class CreateTracksWithOSMIDAction extends Action
{
    use InteractsWithQueue;
    use Queueable;
    use HandlesData;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        if ($fields['osmids']) {
            $osmids = explode(PHP_EOL, $fields['osmids']);

            $successCount = 0;
            $errorCount = [];

            foreach ($osmids as $id) {
                try {
                    $id = trim($id);
                    $track = EcTrack::updateOrCreate(
                        [
                            'user_id' => auth()->user()->id,
                            'osmid' => intval($id),
                            'name' => ''
                        ]
                    );
                    $track->updateDataChain($track);
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    array_push($errorCount, $id);
                }
            }

            $message = 'Processed ' . $successCount . ' OSM IDs successfully';
            if (!empty($errorCount)) {
                $message .= ', but encountered errors for ' . implode(", ", $errorCount);
            }

            return Action::message($message);
        } else {
            return Action::danger('No OSM IDs provided.');
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
            Textarea::make('OSM IDs', 'osmids')
                ->help('For multiple IDs put each of them on a seperate line.'),
        ];
    }

    public function name()
    {
        return 'Create/update ecTrack With OSM ID';
    }
}
