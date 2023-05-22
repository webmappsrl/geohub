<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OverlayLayer extends Model
{
    use HasFactory, HasTranslations;


    /**
     * The attributes translatable
     * @var array
     * 
     */
    public $translatable = ['label'];

    /**
     * Define the relationship with the App model
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * 
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function layers(): HasMany
    {
        return $this->hasMany(Layer::class);
    }
}
