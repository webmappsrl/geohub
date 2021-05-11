<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class TaxonomyTarget extends Model
{
    use HasFactory;

    public function author()
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function save(array $options = [])
    {
        static::creating(function ($taxonomyTarget) {
            $taxonomyTarget->author()->associate(User::getEmulatedUser());
        });
        parent::save($options);
    }
}
