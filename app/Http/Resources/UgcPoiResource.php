<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UgcPoiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'app_id' => $this->app_id,
            'name' => $this->name,
            'geometry' => $this->geometry,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
