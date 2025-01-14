<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Translatable\HasTranslations;

class OverlayLayer extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The attributes translatable
     *
     * @var array
     */
    public $translatable = ['label'];

    protected static function booted()
    {
        static::updating(function ($overlay) {
            $overlay = $overlay;
            if ($overlay->isDirty('default') && $overlay->default) {
                $overlayLayers = $overlay->app->overlayLayers;
                if ($overlayLayers->count() > 1) {
                    foreach ($overlayLayers as $item) {
                        if ($item->id != $overlay->id) {
                            $item->default = false;
                            $item->save();
                        }
                    }
                }
            }
        });
    }

    /**
     * Define the relationship with the App model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function layers(): MorphToMany
    {
        return $this->morphToMany(Layer::class, 'layerable');
    }
}
