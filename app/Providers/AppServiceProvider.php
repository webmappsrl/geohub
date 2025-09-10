<?php

namespace App\Providers;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

\Spatie\NovaTranslatable\Translatable::defaultLocales(['it', 'en']);

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
        /**
         * Passes the language parameters to language switcher partial
         */
        view()->composer('partials.language_switcher', function ($view) {
            $view->with('current_locale', app()->getLocale());
            $view->with('available_locales', config('app.available_locales'));
        });

        UgcMedia::deleting(function ($model) {
            $model->ugc_tracks()->sync([]);
            $model->ugc_pois()->sync([]);
            $model->taxonomy_wheres()->sync([]);
            if (Storage::disk('public')->exists($model->relative_url)) {
                Storage::disk('public')->delete($model->relative_url);
            }
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
            $header = <<<'HEADER'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<Placemark>
HEADER;
            $footer = <<<'HEADER'
</Placemark>
</kml>
HEADER;

            return Response::make($header . $value . $footer, $status, $headers, $options);
        });

        /**
         * Response::gpx()
         */
        Response::macro('gpx', function ($value, int $status = 200, array $headers = [], array $options = []) {
            $header = <<<'HEADER'
<?xml version="1.0"?>
<gpx version="1.1" creator="GDAL 2.2.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ogr="http://osgeo.org/gdal" xmlns="http://www.topografix.com/GPX/1/1" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">
<trk>
HEADER;
            $footer = <<<'HEADER'
</trk>
</gpx>
HEADER;

            return Response::make($header . $value . $footer, $status, $headers, $options);
        });
    }
}
