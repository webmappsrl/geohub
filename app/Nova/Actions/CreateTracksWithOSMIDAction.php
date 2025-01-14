<?php

namespace App\Nova\Actions;

use App\Jobs\UpdateTrackFromOsmJob;
use App\Models\EcTrack;
use App\Traits\HandlesData;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Textarea;

class CreateTracksWithOSMIDAction extends Action
{
    use HandlesData;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Perform the action on the given models.
     *
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
                    $track = EcTrack::where('user_id', auth()->user()->id)
                        ->where('osmid', intval($id))
                        ->first();

                    if (! $track) {
                        // Se non esiste, crea una nuova traccia
                        $track = new EcTrack;
                        $track->user_id = auth()->user()->id;
                        $track->osmid = intval($id);
                        $track->name = '';  // Imposta il nome come stringa vuota o un valore predefinito

                    }
                    $track->saveQuietly();
                    UpdateTrackFromOsmJob::dispatchSync($track);

                    $track->save();
                    $successCount++;  // Incrementa il conteggio delle operazioni riuscite
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                    array_push($errorCount, $id);
                }
            }

            $message = 'Processed '.$successCount.' OSM IDs successfully';
            if (! empty($errorCount)) {
                $message .= ', but encountered errors for '.implode(', ', $errorCount);
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
