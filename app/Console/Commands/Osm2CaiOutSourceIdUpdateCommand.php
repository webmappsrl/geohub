<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Models\OutSourceFeature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Osm2CaiOutSourceIdUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osm2cai:source-id-update {--reverse}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates out_source_features source_id column for hiking routes imported from OSM2CAI';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('reverse')) {
            $this->reverse();
        } else {
            $this->update();
        }
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function update()
    {
        $this->getOsm2CaiOutSourceFeatures()->each(function ($osf) {
            try {
                $hr = $this->getOldHikingRouteByColAndValue('id', $osf->source_id);
                $osf->source_id = 'R' . $hr->relation_id;
                //$this->line('Updating hiking route with ID: ' . $hr->id . ' to osm2cai2 ' . $osf->source_id . ' source id');
                $osf->save();
            } catch (Exception $e) {
                $this->error('Unable to find an hiking route on connection out_source_osm2cai_old with ID: ' . $osf->source_id);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function reverse()
    {
        $this->getOsm2CaiOutSourceFeatures()->each(function ($osf) {
            if (is_numeric(substr($osf->source_id, 0, 1)))
                return;
            try {
                $relation_id = substr($osf->source_id, 1);
                $hr = $this->getOldHikingRouteByColAndValue('relation_id', $relation_id);
                $osf->source_id = $hr->id;
                //$this->line('Updating hiking route with ID: ' . $hr->id . ' to osm2cai1 ' . $hr->id  . ' source id');
                $osf->save();
            } catch (Exception $e) {
                $this->error('Unable to find an hiking route on connection out_source_osm2cai_old with relation id: ' . $relation_id);
            }
        });
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

    protected function getOldHikingRouteByColAndValue($col, $val)
    {
        return $this->getOldHikingRoutesDbConnection()->where($col, $val)->first();
    }
}
