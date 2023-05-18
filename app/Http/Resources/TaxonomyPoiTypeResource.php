<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaxonomyPoiTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        //todo fix name, it only displays in default language (it).
        return [
            'id' => $this->id,
            'name' => $this->name,
            'identifier' => $this->identifier,
        ];
    }
}
