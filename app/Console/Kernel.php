<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        // mondays tuesdays wednesdays thursdays fridays saturdays sundays
        // GEOHUB DB DUMP ogni giorno alle 6
        $schedule->command('geohub:dump_db')->dailyAt('6:00');

        // ###########################################################
        // # SUB DAYLY Script
        // ###########################################################

        // Sardegna Sentieri ogni ora
        $schedule->exec('bash /root/geohub.webmapp.it/scripts/sardegna_sentieri_import_sync_updated_at.sh')->hourly();

        // ###########################################################
        // # DAYLY Script
        // ###########################################################

        // Index CAIPARMA
        $schedule->exec('bash /root/geohub.webmapp.it/scripts/cai_parma_osm_poi_updated_at.sh')->dailyAt('18:00');
        $schedule->command('geohub:update_pois_from_osm caiparma@webmapp.it')->dailyAt('20:15');
        $schedule->command('geohub:feature_to_gallery poi 20703')->dailyAt('20:55');
        $schedule->command('geohub:update_track_from_osm caiparma@webmapp.it "carlopr54@gmail.com"')->dailyAt('21:15');

        // Index CAIPONTEDERA
        $schedule->command('geohub:update_pois_from_osm caipontedera@webmapp.it')->dailyAt('18:15');
        $schedule->command('geohub:update_track_from_osm caipontedera@webmapp.it')->dailyAt('19:15');

        // Index BLUBELL
        $schedule->command('geohub:index-tracks 48')->dailyAt('05:00');
        // Index PARCO MAREMMA
        $schedule->command('geohub:index-tracks 18 --no-elastic')->dailyAt('05:10');
        // Index FIE
        $schedule->command('geohub:index-tracks 29 --no-elastic')->dailyAt('05:20');

        // ###########################################################
        // # SPECIAL PROJECT
        // ###########################################################

        // EUMA

        $schedule->exec('bash /root/geohub.webmapp.it/scripts/euma_sync_updated_at.sh')->mondays()->at('4:00');

        // HORIZON
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        // TELESCOPE PRUNING
        // The following command will delete all records older than 1 days https://laravel.com/docs/11.x/telescope#data-pruning
        $schedule->command('telescope:prune --hours=12')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
