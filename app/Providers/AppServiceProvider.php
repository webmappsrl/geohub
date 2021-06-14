<?php

namespace App\Providers;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

define('CONTENT_TYPE_AUDIO_MAPPING', [
    'mpeg' => 'audio/mpeg',
    'mp3' => 'audio/mpeg',
    'mp4' => 'audio/mpeg',
    'ogg' => 'audio/ogg',
    'wav' => 'audio/wav',
]);

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        UgcMedia::deleting(function ($model) {
            $model->ugc_tracks()->sync([]);
            $model->ugc_pois()->sync([]);
            $model->taxonomy_wheres()->sync([]);
            if (Storage::disk('public')->exists($model->relative_url))
                Storage::disk('public')->delete($model->relative_url);
        });
        UgcTrack::deleting(function ($model) {
            $model->ugc_media()->sync([]);
            $model->taxonomy_wheres()->sync([]);
        });
        UgcPoi::deleting(function ($model) {
            $model->ugc_media()->sync([]);
            $model->taxonomy_wheres()->sync([]);
        });

        /**
         * Response::kml()
         */
        Response::macro('kml', function ($value, int $status = 200, array $headers = [], array $options = []) {
            $header = <<<HEADER
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<Placemark>
HEADER;
            $footer = <<<HEADER
</Placemark>
</kml>
HEADER;
            return Response::make($header . $value . $footer, $status, $headers, $options);
        });
    }
}
