<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxonomyActivity extends Model
{
    use HasFactory;

    public function author()
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function save(array $options = [])
    {
        static::creating(function ($taxonomyActivity) {
            $taxonomyActivity->author()->associate(User::getEmulatedUser());
        });
        parent::save($options);
    }
}
