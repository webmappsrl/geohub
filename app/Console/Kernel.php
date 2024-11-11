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
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        // GEOHUB DB DUMP ogni giorno alle 6
        // $schedule->command('geohub:dump_db')->dailyAt('6:00');

        // Index BLUBELL ogni giorno alle 20:30
        $schedule->command('geohub:index-tracks 48')->dailyAt('20:30');

        // Index CAIPARMA
        $schedule->exec('bash /root/scripts/cai_parma_osm_poi_updated_at.sh')->dailyAt('18:00');
        $schedule->command('geohub:update_pois_from_osm caiparma@webmapp.it')->dailyAt('20:15');
        $schedule->command('geohub:feature_to_gallery poi 20703')->dailyAt('20:55');
        $schedule->command('geohub:update_track_from_osm caiparma@webmapp.it "carlopr54@gmail.com"')->dailyAt('21:15');

        // Index PARCO MAREMMA
        $schedule->command('geohub:index-tracks 18 --no-elastic')->dailyAt('21:30');

        // Index FIE
        $schedule->command('geohub:index-tracks 29 --no-elastic')->dailyAt('20:00');

        // Sardegna Sentieri ogni ora
        $schedule->exec('bash /root/scripts/sardegna_sentieri_import_sync_updated_at.sh')->dailyAt('6:00');

        // Import and Sync OSM2CAI
        $schedule->exec('bash /root/geohub.webmapp.it/scripts/import_sync_osm2cai_all.sh')->saturdays()->at('22:00');
        $schedule->exec('bash /root/scripts/osm2cai_hoqu_script.sh')->sundays()->at('1:00');
        $schedule->command('geohub:index-tracks 15')->sundays()->at('2:00');
        $schedule->command('geohub:index-tracks 26')->sundays()->at('4:00');
        $schedule->command('geohub:generate_dem 26 dem')->sundays()->at('5:00');

        // Sync Itinera Romanica Plus (se serve, rimuovi il commento)
        // $schedule->exec('bash /root/scripts/ir_import_sync_hoqu.sh')->dailyAt('23:00');

        $schedule->exec('bash /root/scripts/euma_sync_updated_at.sh')->dailyAt('3:00');
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
