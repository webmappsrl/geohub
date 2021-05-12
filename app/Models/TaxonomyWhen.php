<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxonomyWhen extends Model
{
    use HasFactory;

    public function author()
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function save(array $options = [])
    {
        static::creating(function ($taxonomyWhen) {
            $taxonomyWhen->author()->associate(User::getEmulatedUser());
        });
        parent::save($options);
    }
}
