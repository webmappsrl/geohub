<?php

namespace App\Console\Commands;

use App\Models\EcMedia;
use App\Models\EcTrack;
use Illuminate\Console\Command;

class ImportEcTrack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:import_ec_track,
                            {path : the path of the GPX file to import}
                            {user_id : the user id creating the new import}
                            {name? : the name of the imported EcTrack}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to import a new ec_track with a GPX file';

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

        $url = $this->argument('path');
        $file = @file_get_contents($url);
        $fileName = basename($this->argument('path'));
        if ($file === FALSE)
            return $this->error('Error, file does not exists');
        $contents = file_get_contents($url);

        $command = "ogr2ogr -f PostgreSQL PG:\"";
        $command .= "dbname='" . config("database.connections." . config("database.default") . ".database") . "' ";
        $command .= "host='" . config("database.connections." . config("database.default") . ".host") . "' ";
        $command .= "port='" . config("database.connections." . config("database.default") . ".port") . "' ";
        $command .= "user='" . config("database.connections." . config("database.default") . ".username") . "' ";
        $command .= "password='" . config("database.connections." . config("database.default") . ".password") . "' ";
        //$command .= "table=ec_tracks";
        $command .= "\" $fileName -sql \"Select * From tracks\" ";

        //dd($command);
        $result = exec($command);
        dd($result);
        if ($this->argument('name')) {
            $newEcTrack = EcTrack::create(['name' => $this->argument('name'), 'user_id' => $this->argument('user_id'), 'geometry' => $geometry]);
            $newEcTrack->save();
        } else {
            $newEcTrack = EcTrack::create(['name' => $fileName, 'user_id' => $this->argument('user_id'), 'geometry' => $geometry]);
            $newEcTrack->save();
        }
        return 0;
    }
}
