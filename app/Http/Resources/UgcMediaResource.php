<?php

namespace App\Http\Resources;

use App\Models\UgcMedia;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UgcMediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $geom = UgcMedia::where('id', '=', $this->id)
            ->select(
                DB::raw('ST_AsGeoJSON(geometry) as geom')
            )
            ->first()
            ->geom;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'app_id' => $this->app_id,
            'name' => $this->name,
            'description' => $this->description,
            'relative_url' => $this->relative_url,
            'geometry' => json_decode($geom),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
