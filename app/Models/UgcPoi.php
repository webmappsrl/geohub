<?php

namespace App\Models;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class UgcPoi extends Model
{
    use HasFactory, GeometryFeatureTrait;

    /**
     * @var mixed|string
     */

    protected $fillable = [
        'app_id',
    ];

    public function ugc_media()
    {
        return $this->belongsToMany(UgcMedia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}