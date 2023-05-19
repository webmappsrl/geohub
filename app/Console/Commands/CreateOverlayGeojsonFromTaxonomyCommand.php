<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateOverlayGeojsonFromTaxonomyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:createOverlayGeojson
                            {app_id : ID of the App} 
                            {overlay_id : ID of the interactive overlay layer} 
                            {name : the name of the generated file} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command creates a featureCollection file that has all the geometries of the selected taxonomyWheres and puts correlated layer information in the properties of each feature.';

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
        return 0;
    }
}
