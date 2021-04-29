<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports geo-data from shp file';

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
        $geoData = file_get_contents(base_path('comuni.geojson'));
        $original_data = json_decode($geoData, true);
        $features = array();

        foreach($original_data['features'] as $key => $value) { 
            $features[] = array(
                /*$key => $value,*/
                /*'type' => $value['type'],*/

                    /*'geometry' => array('type' => 'MultiPolygon', 'coordinates' => array((float)$value['coordinates'],(float)$value['coordinates'])),
                    'properties' => array(array('COMUNE' => $value['COMUNE']), array('COD_CM' => $value['COD_CM'])),*/
                );
        };   

        $allfeatures = array('type' => 'FeatureCollection', 'features' => $features);
        print json_encode($allfeatures, JSON_PRETTY_PRINT);
    }
}
