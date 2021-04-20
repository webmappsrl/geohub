<?php

namespace App\Models;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UgcMedia extends Model {
    use HasFactory, GeometryFeatureTrait;

    public function ugc_pois() {
        return $this->belongsToMany(UgcPoi::class);
    }

    public function ugc_tracks() {
        return $this->belongsToMany(UgcTrack::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
