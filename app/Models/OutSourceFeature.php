<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutSourceFeature extends Model
{
    protected $table = 'out_source_features';

    protected $fillable = [
        'provider','source_id','type','endpoint','raw_data','geometry','tags','endpoint_slug'
    ];
    
    protected $casts = [
        'tags' => 'array',
        'source_id' => 'string',
    ];

    public function getName(): string {
        $name = "OutSourceTrack {$this->provider} {$this->source_id} (ID: {$this->id})";
        if (isset($this->tags['name']) && !is_null($this->tags['name'])) {
            $val = array_values($this->tags['name'])[0];
            if(!is_null($val)) {
                return $val;
            }
        }
        return $name;
    }

    // TODO: switch on provider
    public function getNormalizedTags(): array {
        $tags=[];
        if(isset($this->tags['ref'])) {$tags['ref']=$this->tags['ref'];}
        if(isset($this->tags['cai_scale'])) {$tags['cai_scale']=$this->tags['cai_scale'];}
        if(isset($this->tags['from'])) {$tags['from']=$this->tags['from'];}
        if(isset($this->tags['to'])) {$tags['to']=$this->tags['to'];}
        return $tags;
    }

}
