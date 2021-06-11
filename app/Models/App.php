<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class App extends Model
{
    use HasFactory;
    
    public function author()
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

}
