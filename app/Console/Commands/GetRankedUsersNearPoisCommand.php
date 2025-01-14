<?php

namespace App\Console\Commands;

use App\Models\App;
use Illuminate\Console\Command;

class GetRankedUsersNearPoisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:get_ranked_users_near_pois {--app_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get ranked users near pois';

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
        if ($this->option('app_id')) {
            $app = App::where('id', $this->option('app_id'))->first();
            if (! $app) {
                $this->error('App with id '.$this->option('app_id').' not found!');

                return;
            }
            if (! $app->app_id) {
                $this->error('This app does not have app_id! Please add app_id. (e.g. it.webmapp.webmapp)');

                return;
            }

            $app->classification = $app->getRankedUsersNearPois();
            $app->save();

            return;
        }
        $this->error('app_id not found! Please provide app_id as an option. (e.g. --app_id=1)');

    }
}
