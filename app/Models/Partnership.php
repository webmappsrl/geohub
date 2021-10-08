<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Partnership extends Model {
    use HasFactory;

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class, 'partnership_user');
    }

    public function ecTracks(): BelongsToMany {
        return $this->belongsToMany(EcTrack::class, 'ec_track_partnership');
    }
}
