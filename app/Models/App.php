<?php

namespace App\Models;

use App\Providers\HoquServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class App extends Model
{
    use HasFactory;

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($ecMedia) {
            $user = User::getEmulatedUser();
            if (is_null($user)) $user = User::where('email', '=', 'team@webmapp.it')->first();
            $ecMedia->author()->associate($user);
        });
    }

    public function author()
    {
        return $this->belongsTo("\App\Models\User", "user_id", "id");
    }

    public function getGeojson()
    {
        $tracks = EcTrack::where('user_id', $this->user_id)->get();

        if (!is_null($tracks)) {
            $geoJson = ["type" => "FeatureCollection"];
            $features = [];
            foreach ($tracks as $track) {
                $features[] = $track->getGeojson();
            }
            $geoJson["features"] = $features;
            return json_encode($geoJson);
        }
    }
}
