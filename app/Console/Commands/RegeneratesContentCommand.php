<?php

namespace App\Console\Commands;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Providers\HoquServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RegeneratesContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:regenerates
                            {model : Name of the model class that must be regenerated (EcTrack, EcMEdia)}
                            {--id= : Pass the model id that must be regenerated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This commands sends to HOQU all the needed to regenerate via geomixer the specific content.';

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
        $model = $this->argument('model');
        $id = $this->option('id');
        $msg = '';
        switch ($model) {
            case 'EcTrack':
                $this->info('Sending store to HOQU for EcTrack');
                if (isset($id)) {
                    $tracks = EcTrack::all()->where('id', $id);
                    $msg = ' with id ' . $id;
                } else {
                    $tracks = EcTrack::all();
                }
                if (count($tracks) == 0) {
                    $this->warn('No EcTracks found in geohub' . $msg);
                } else {
                    foreach ($tracks as $track) {
                        $this->info('Hoqu Store for track: ' . $track->id);
                        try {
                            $hoquServiceProvider = app(HoquServiceProvider::class);
                            $hoquServiceProvider->store('enrich_ec_track', ['id' => $track->id]);
                        } catch (\Exception $e) {
                            Log::error('An error occurred during a store operation: ' . $e->getMessage());
                        }
                    }
                }
                break;
            case 'EcPoi':
                $this->info('Sending store to HOQU for EcPoi');
                if (isset($id)) {
                    $pois = EcPoi::all()->where('id', $id);
                    $msg = ' with id ' . $id;
                } else {
                    $pois = EcPoi::all();
                }
                if (count($pois) == 0) {
                    $this->warn('No EcPois found in geohub' . $msg);
                } else {
                    foreach ($pois as $poi) {
                        $this->info('Hoqu Store for poi: ' . $poi->id);
                        try {
                            $hoquServiceProvider = app(HoquServiceProvider::class);
                            $hoquServiceProvider->store('enrich_ec_poi', ['id' => $poi->id]);
                        } catch (\Exception $e) {
                            Log::error('An error occurred during a store operation: ' . $e->getMessage());
                        }
                    }
                }
                break;
            case 'EcMedia':
                $this->info('Sending store to HOQU for EcMedia');
                if (isset($id)) {
                    $medias = EcMedia::all()->where('id', $id);
                    $msg = ' with id ' . $id;
                } else {
                    $medias = EcMedia::all();
                }
                if (count($medias) == 0) {
                    $this->warn('No EcMedia found in geohub' . $msg);
                } else {
                    foreach ($medias as $media) {
                        $this->info('Hoqu Store for media: ' . $media->id);
                        try {
                            $hoquServiceProvider = app(HoquServiceProvider::class);
                            $hoquServiceProvider->store('enrich_ec_media', ['id' => $media->id]);
                        } catch (\Exception $e) {
                            Log::error('An error occurred during a store operation: ' . $e->getMessage());
                        }
                    }
                }
                break;
            default:
                $this->error('Invalid model ' . $model . '. Available model: EcTrack, EcMedia');
                break;
        }

        return 0;
    }
}
