<?php

namespace App\Nova\Actions;

use App\Http\Facades\OsmClient;
use App\Models\EcTrack;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\Textarea;

class CreateTracksWithOSMIDAction extends Action
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
        if ($fields['osmids']) {
            $ids = explode(PHP_EOL, $fields['osmids']);

            $successCount = 0;
            $errorCount = [];

            foreach ($ids as $id) {
                try {
                    $id = trim($id);
                    $osmClient = new OsmClient();
                    $geojson_content = $osmClient::getGeojson('relation/' . $id);
                    $geojson_content = json_decode($geojson_content, true);
                    if (empty($geojson_content['geometry']) || empty($geojson_content['properties'])) {
                        throw new Exception('Wrong OSM ID');
                    }
                    $geojson_geometry = json_encode($geojson_content['geometry']);
                    $geometry = DB::select("SELECT ST_AsText(ST_Force3D(ST_LineMerge(ST_GeomFromGeoJSON('" . $geojson_geometry . "')))) As wkt")[0]->wkt;

                    $name_array = array();

                    if (array_key_exists('ref', $geojson_content['properties']) && !empty($geojson_content['properties']['ref'])) {
                        array_push($name_array, $geojson_content['properties']['ref']);
                    }
                    if (array_key_exists('name', $geojson_content['properties']) && !empty($geojson_content['properties']['name'])) {
                        array_push($name_array, $geojson_content['properties']['name']);
                    }
                    $trackname = !empty($name_array) ? implode(' - ', $name_array) : null;
                    $trackname = str_replace('"', '', $trackname);

                    $track = EcTrack::updateOrCreate(
                        [
                            'user_id' => auth()->user()->id,
                            'osmid' => intval($id),
                        ]
                    );

                    $track->name = $trackname;
                    $track->geometry = $geometry;
                    $track->osmid = intval($id);
                    $track->ref = $geojson_content['properties']['ref'];

                    //check if ascent, descent, distance duration_forward and duration_backward are not null in the geojson data and if so, update the $track
                    $track->cai_scale = (key_exists('cai_scale', $geojson_content['properties']) && $geojson_content['properties']['cai_scale']) ? $geojson_content['properties']['cai_scale'] : $track->cai_scale;
                    $track->from = (key_exists('from', $geojson_content['properties']) && $geojson_content['properties']['from']) ? $geojson_content['properties']['from'] : $track->from;
                    $track->to = (key_exists('to', $geojson_content['properties']) && $geojson_content['properties']['to']) ? $geojson_content['properties']['to'] : $track->to;
                    $track->ascent = (key_exists('ascent', $geojson_content['properties']) && $geojson_content['properties']['ascent']) ? $geojson_content['properties']['ascent'] : $track->ascent;
                    $track->descent = (key_exists('descent', $geojson_content['properties']) && $geojson_content['properties']['descent']) ? $geojson_content['properties']['descent'] : $track->descent;
                    $track->distance = (key_exists('distance', $geojson_content['properties']) && $geojson_content['properties']['distance']) ? str_replace(',', '.', $geojson_content['properties']['distance']) : $track->distance;
                    //duration forward must be converted to minutes
                    if (key_exists('duration:forward', $geojson_content['properties']) && $geojson_content['properties']['duration:forward'] != null) {
                        $duration_forward = str_replace('.', ':', $geojson_content['properties']['duration:forward']);
                        $duration_forward = str_replace(',', ':', $duration_forward);
                        $duration_forward = str_replace(';', ':', $duration_forward);
                        $duration_forward = explode(':', $duration_forward);
                        $track->duration_forward = ($duration_forward[0] * 60) + $duration_forward[1];
                    }
                    //same for duration_backward
                    if (key_exists('duration:backward', $geojson_content['properties']) && $geojson_content['properties']['duration:backward'] != null) {
                        $duration_backward = str_replace('.', ':', $geojson_content['properties']['duration:backward']);
                        $duration_backward = str_replace(',', ':', $duration_backward);
                        $duration_backward = str_replace(';', ':', $duration_backward);
                        $duration_backward = explode(':', $duration_backward);
                        $track->duration_backward = ($duration_backward[0] * 60) + $duration_backward[1];
                    }
                    $track->skip_geomixer_tech = true;
                    $track->save();

                    $successCount++;
                } catch (\Exception $e) {
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
