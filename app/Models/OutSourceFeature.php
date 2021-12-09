<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutSourceFeature extends Model
{
    protected $table = 'out_source_features';

    protected $fillable = [
        'provider','source_id','type'
    ];
    protected $casts = [
        'tags' => 'array',
    ];

    public function getName(): string {
        $name = "OutSourceTrack {$this->provider} {$this->source_id} (ID: {$this->id})";
        if (isset($this->tags['name'])) {
            return array_values($this->tags['name'])[0];
        }
        return $name;
    }

}
