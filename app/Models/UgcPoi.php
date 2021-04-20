<?php

namespace App\Models;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UgcPoi extends Model {
    use HasFactory, GeometryFeatureTrait;

    public function ugc_media() {
        return $this->belongsToMany(UgcMedia::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
