<?php

use App\Models\OutSourceFeature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Migrations\Migration;

class UpdateOutSourceFeatureOsm2caiSourceIdMigration extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->getOsm2CaiOutSourceFeatures()->each(function ($osf) {
            try {
                $hr = $this->getOldHikingRouteByColAndValue('id', $osf->source_id);
                $osf->source_id = 'R' . $hr->relation_id;
                $osf->save();
            } catch (Exception $e) {
                Log::error('Unable to find an hiking route on connection out_source_osm2cai_old with ID: ' . $osf->source_id);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->getOsm2CaiOutSourceFeatures()->each(function ($osf) {
            if (is_numeric(substr($osf->source_id, 0, 1)))
                return;
            try {
                $relation_id = substr($osf->source_id, 1);
                $hr = $this->getOldHikingRouteByColAndValue('relation_id', $relation_id);
                $osf->source_id = $hr->id;
                $osf->save();
            } catch (Exception $e) {
                Log::error('Unable to find an hiking route on connection out_source_osm2cai_old with relation id: ' . $relation_id);
            }
        });
    }

    protected function getOldHikingRouteByColAndValue($col, $val)
    {
        return $this->getOldHikingRoutesDbConnection()->where($col, $val)->first();
    }


    protected function getOldHikingRoutesDbConnection()
    {
        return DB::connection('out_source_osm2cai_old')->table('hiking_routes');
    }

    protected function getOsm2CaiOutSourceFeatures()
    {
        return OutSourceFeature::where('provider', 'App\Classes\OutSourceImporter\OutSourceImporterFeatureOSM2CAI')
            ->where('endpoint', 'LIKE', 'https://osm2cai.cai.it/api/v1/hiking-routes%')
            ->get()
        ;
    }
}
