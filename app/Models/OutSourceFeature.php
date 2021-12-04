<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutSourceFeature extends Model
{
    use HasFactory;

    protected $casts = [
        'tags' => 'array',
    ];
}
