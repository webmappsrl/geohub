<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\EcMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

const EC_MEDIA_URL = 'https://ecmedia.s3.eu-central-1.amazonaws.com/EcMedia/';

class FixEcMediaNullUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:regenerate_ec_media_urls {appId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate ec media urls with s3 public url for the given app';

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
        $this->info('Starting regenerate ec media urls command');

        $appId = $this->argument('appId');
        if (! is_numeric($appId)) {
            $this->error('App id is not a number');

            return 1;
        }

        $app = App::where('id', $appId)->first();

        if (! $app) {
            $this->error('App not found');

            return 1;
        }

        $author = $app->author;

        $ecMedia = EcMedia::where('user_id', $author->id)
            ->where(function ($query) {
                $query->whereNull('url')
                    ->orWhere('url', '');
            })
            ->get();

        $count = $ecMedia->count();
        $this->info("Found {$count} media items with null URLs");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($ecMedia as $media) {
            $decodedName = urldecode((string) $media->name);
            $media->name = $decodedName;
            $media->saveQuietly();

            $mediaUrl = EC_MEDIA_URL.$media->id.'.jpg';

            $response = Http::get($mediaUrl);
            if (! $response->successful()) {
                $this->newLine();
                $this->error("Media {$media->id} not found");

                continue;
            }

            $media->url = $mediaUrl;
            $media->saveQuietly();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Finished updating media URLs');

        return 0;
    }
}
