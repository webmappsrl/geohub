<?php

namespace App\Console\Commands;

use App\Models\App;
use Illuminate\Console\Command;

class GenerateAppsQrCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:generate_qr_code_for_apps {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will generate/refresh the QR code for the app with the given name. If no app name is provided it will generate a QR code for all apps.';

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
        //if app name is provided, generate QR code for that app
        if ($this->argument('name')) {
            $app = App::where('name', $this->argument('name'))->first();
            if (!$app) {
                $this->error('App with name ' . $this->argument('name') . ' not found!');
                return;
            }
            $app->generateQrCode();
            $this->info('QR code generated for app: ' . $app->name);
            return;
        }
        //if no app name is provided, generate QR code for all apps 
        $apps = App::all();
        foreach ($apps as $app) {
            $app->generateQrCode();
            $this->info('QR code generated for app: ' . $app->name);
        }
    }
}
