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

class UpdateEcTrack3DDemJob implements ShouldQueue
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
                }
            } catch (\Exception $e) {
                Log::error('An error occurred during 3D DEM operation: ' . $e->getMessage());
            }
        } else {
            // Request failed, handle the error here
            $errorCode = $response->status();
            $errorBody = $response->body();
            Log::error("Error {$errorCode}: {$errorBody}");
        }
    }
}
