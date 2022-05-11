<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncEcFeatureFromOutSourceFeatureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:sync-ec-from-out-source
                            {type : Set the Ec type (track, poi, media, taxonomy)}
                            {author : Set the author that must be assigned to EcFeature crested, use email }
                            {--app : Alternative way to set the EcFeature Author. Take the app author and set the same author. Use app ID}
                            {--provider : Set the provider of the Out Source Features}
                            {--endpoint : Set the endpoint of the Out Source Features}
                            {--activity : Set the identifier activity taxonomy that must be assigned to EcFeature created}
                            {--name_format : Set how the command must save the name. Is a string with curly brackets notation to use dynamics tags value}';

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
        return 0;
    }
}
