<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportEcTracFromFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:import_track_from_file
                            {path : the storage path of the excel file to import data from (in the importer disk) e.g. [storage/importer/]fie/track.csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and update EcTrack data from a CSV file';

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
        $filePath = 'storage/importer/' . $this->argument('path');
        $this->info("Importing EcTrack data from file: $filePath");
        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\EcTrackFromCSV, $filePath);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
        $this->info("Imported EcTrack data from file: $filePath");
        return 0;
    }
}
