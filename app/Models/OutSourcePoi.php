<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutSourcePoi extends OutSourceFeature
{
    use HasFactory;

    private $type = 'poi';

    public function ecPois(): HasMany
    {
        return $this->hasMany(EcPoi::class);
    }
}
