<?php

namespace App\Models;

use App\Traits\GeometryFeatureTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class UgcPoi extends Model
{
    use HasFactory, GeometryFeatureTrait;

    public function ugc_media()
    {
        return $this->belongsToMany(UgcMedia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function save(array $options = [])
    {
        parent::save($options);
        try {
            $this->hoquServiceProvider->store('update_ugc_taxonomy_where', ['id' => $this->id]);
        } catch (\Exception $e) {
            Log::error('An error occurred during a store operation: ' . $e->getMessage());
        }
    }
}
