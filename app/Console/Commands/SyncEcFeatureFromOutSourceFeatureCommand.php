<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Classes\EcSynchronizer\SyncEcFromOutSource;

class SyncEcFeatureFromOutSourceFeatureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:sync-ec-from-out-source
                            {type : Set the Ec type (track, poi, media, taxonomy)}
                            {author : Set the author that must be assigned to EcFeature crested, use email or ID }
                            {--app= : Alternative way to set the EcFeature Author. Take the app author and set the same author. Use app ID}
                            {--P|provider= : Set the provider of the Out Source Features}
                            {--E|endpoint= : Set the endpoint of the Out Source Features}
                            {--activity= : Set the identifier activity taxonomy that must be assigned to EcFeature created}
                            {--poi_type= : Set the identifier poi_type taxonomy that must be assigned to EcFeature created, the default is poi}
                            {--name_format=name : Set how the command must save the name. Is a string with curly brackets notation to use dynamics tags value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command creates or updates EcFeatures from OutSourceFeatures based on given parameters';

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
        $type = $this->argument('type');
        $author = $this->argument('author');

        $provider = '';
        $endpoint = '';
        $activity = '';
        $poi_type = '';
        $name_format = $this->option('name_format');
        $app = 0;

        if ($this->option('provider'))
            $provider = $this->option('provider');
            
        if ($this->option('endpoint'))
            $endpoint = $this->option('endpoint');
        
        if ($this->option('activity'))
            $activity = $this->option('activity');
        
        if ($this->option('poi_type'))
            $poi_type = $this->option('poi_type');
        
        if ($this->option('app'))
            $app = $this->option('app');

        $SyncEcFromOutSource = new SyncEcFromOutSource($type,$author,$provider,$endpoint,$activity,$name_format,$app,$poi_type);

        if ($SyncEcFromOutSource->checkParameters()) {
            $ids_array = $SyncEcFromOutSource->getList();
            
            if (!empty($ids_array)) {
                $loop = $SyncEcFromOutSource->sync($ids_array);
            }
        }

    }
}
