<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UgcPoiResource extends UgcResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ugcGeojson = parent::toArray($request);

        $ugcGeojson['properties']['media_ids'] = $this->ugc_media->pluck('id');

        return $ugcGeojson;
    }
}
