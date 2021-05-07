<?php

namespace App\Providers;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
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
    }
}
