<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'app_id' => $this->app_id,
            'name' => $this->name,
            'description' => $this->description,
            'relative_url' => $this->relative_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'geometry' => $this->geometry->getValue(),
        ];
    }
}
