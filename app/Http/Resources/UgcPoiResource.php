<?php

namespace App\Http\Resources;

class UgcPoiResource extends UgcResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ugcGeojson = parent::toArray($request);

        $ugcGeojson['properties']['media_ids'] = $this->ugc_media->pluck('id');

        return $ugcGeojson;
    }
}
