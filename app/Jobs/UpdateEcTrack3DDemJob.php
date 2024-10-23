<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\WithoutOverlappingBaseJob;

class UpdateEcTrack3DDemJob extends WithoutOverlappingBaseJob
{
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
            rtrim(config('services.dem.host'), '/') . rtrim(config('services.dem.3d_data_api'), '/'),
            $data
        );

        // Check the response
        if ($response->successful()) {
            // Request was successful, handle the response data here
            $responseData = $response->json();
            try {
                if (isset($responseData['geometry']) && !empty($responseData['geometry'])) {
                    $this->ecTrack->geometry = DB::select("SELECT ST_GeomFromGeoJSON('" . json_encode($responseData['geometry']) . "') As wkt")[0]->wkt;
                    $this->ecTrack->saveQuietly();
                    Log::info($this->ecTrack->id . ' UpdateEcTrack3DDemJob: SUCCESS');
                }
            } catch (\Exception $e) {
                Log::error($this->ecTrack->id . 'UpdateEcTrack3DDemJob: FAILED: ' . $e->getMessage());
            }
        } else {
            // Request failed, handle the error here
            $errorCode = $response->status();
            $errorBody = $response->body();
            Log::error($this->ecTrack->id . "UpdateEcTrack3DDemJob: FAILED: Error {$errorCode}: {$errorBody}");
        }
    }
}
