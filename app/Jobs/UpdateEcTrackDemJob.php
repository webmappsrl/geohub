<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateEcTrackDemJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $ecTrack;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ecTrack)
    {
        $this->ecTrack = $ecTrack;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->ecTrack->getTrackGeometryGeojson();
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            rtrim(config('services.dem.host'), '/') . rtrim(config('services.dem.tech_data_api'), '/'),
            $data
        );

        // Check the response
        if ($response->successful()) {
            // Request was successful, handle the response data here
            $responseData = $response->json();
            try {
                if (!$this->ecTrack->skip_geomixer_tech) {
                    if (isset($responseData['properties'])) {
                        if (isset($responseData['properties']['duration_forward_hiking']) && !empty($responseData['properties']['duration_forward_hiking'])) {
                            $this->ecTrack->duration_forward = $responseData['properties']['duration_forward_hiking'];
                        }
                        if (isset($responseData['properties']['duration_backward_hiking']) && !empty($responseData['properties']['duration_backward_hiking'])) {
                            $this->ecTrack->duration_backward = $responseData['properties']['duration_backward_hiking'];
                        }
                        $fields = [
                            'ele_min',
                            'ele_max',
                            'ele_from',
                            'ele_to',
                            'ascent',
                            'descent',
                            'distance',
                        ];

                        foreach ($fields as $field) {
                            if (isset($responseData['properties'][$field]) && !empty($responseData['properties'][$field])) {
                                $this->ecTrack->$field = $responseData['properties'][$field];
                            }
                        }
                    }
                }
                if (isset($responseData['geometry']) && !empty($responseData['geometry'])) {
                    $this->ecTrack->geometry = DB::select("SELECT ST_GeomFromGeoJSON('" . json_encode($responseData['geometry']) . "') As wkt")[0]->wkt;
                    $this->ecTrack->saveQuietly();
                }
            } catch (\Exception $e) {
                Log::error('An error occurred during DEM operation: ' . $e->getMessage());
            }
        } else {
            // Request failed, handle the error here
            $errorCode = $response->status();
            $errorBody = $response->body();
            Log::error("Error {$errorCode}: {$errorBody}");
        }
    }
}
