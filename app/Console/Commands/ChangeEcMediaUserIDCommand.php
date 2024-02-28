<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\EcPoi;
use App\Models\EcTrack;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ChangeEcMediaUserIDCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:change_ecmedia_userid {app_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the user_id of the ec_media records to the user_id of the app';

    protected $user_id;
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
        if ($this->argument('app_id')) {
            $app = App::where('id', $this->argument('app_id'))->first();
            if (!$app) {
                $this->error('App with id ' . $this->argument('app_id') . ' not found!');
                return;
            }

            $this->user_id = $app->user_id;

            $pois = EcPoi::where('user_id', $this->user_id)->get();
            $tracks = EcTrack::where('user_id', $this->user_id)->get();

            if ($pois->count() > 0) {
                $poiCount = $pois->count();
                foreach ($pois as $index => $poi) {
                    Log::info("Processing POI ". ++$index ." of $poiCount.");
                    if ($poi->ecMedia->count() > 0) {
                        $poi->ecMedia->each(function ($media) {
                            Log::info("Processing Gallery Media ". $media->id ."");
                            $media->user_id = $this->user_id;
                            $media->save();
                        });
                    }
                    if (!empty($poi->featureImage)) {
                        $media = $poi->featureImage;
                        Log::info("Processing Feature Image ". $media->id ."");
                        $media->user_id = $this->user_id;
                        $media->save();
                    }
                }
            }

            if ($tracks->count() > 0) {
                $trackCount = $tracks->count();
                foreach ($tracks as $index => $track) {
                    Log::info("Processing TRACK ". ++$index ." of $trackCount.");
                    if ($track->ecMedia->count() > 0) {
                        $track->ecMedia->each(function ($media) {
                            Log::info("Processing Gallery Media ". $media->id ."");
                            $media->user_id = $this->user_id;
                            $media->save();
                        });
                    }
                    if (!empty($track->featureImage)) {
                        $media = $track->featureImage;
                        Log::info("Processing Feature Image ". $media->id ."");
                        $media->user_id = $this->user_id;
                        $media->save();
                    }
                }
            }
        }
    }
}
