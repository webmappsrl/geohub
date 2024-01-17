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

class UpdateEcPoiDemJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $ecPoi;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ecPoi)
    {
        $this->ecPoi = $ecPoi;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $geom = $this->ecPoi->geometry;
            $point_geom = DB::select("SELECT ST_Transform('$geom'::geometry,4326) AS geom")[0]->geom;
            $coordinates = DB::select("SELECT ST_X('$point_geom') as x,ST_Y('$point_geom') AS y")[0];
    
            $response = Http::get(rtrim(config('services.dem.host'), '/') . rtrim(config('services.dem.ele_api'), '/') . "/$coordinates->x/$coordinates->y");
    
            $this->ecPoi->ele = $response->json()['ele'];
            $this->ecPoi->save();
        } catch (\Exception $e) {
            // Handle the exception
            Log::error('Error in UpdateEcPoiDemJob: ' . $e->getMessage());
        }
    }
}
