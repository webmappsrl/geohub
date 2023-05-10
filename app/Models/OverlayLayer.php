<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;

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
     * The attributes that should be cast
     * @var array
     * 
     */
    protected $casts = [
        'file_upload' => 'array',
    ];

    /**
     * Define the relationship with the App model
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * 
     */
    public function apps(): BelongsToMany
    {
        return $this->belongsToMany(App::class);
    }
}
