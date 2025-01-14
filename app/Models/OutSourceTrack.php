<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutSourceTrack extends OutSourceFeature
{
    use HasFactory;

    private $type = 'track';

    public function ecTracks(): HasMany
    {
        return $this->hasMany(EcTrack::class);
    }
}
