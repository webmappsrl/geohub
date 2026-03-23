<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UgcMediaResource extends UgcResource
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

        $ugcGeojson['properties']['relative_url'] = $this->relative_url;
        $ugcGeojson['properties']['ugc_pois'] = $this->ugc_pois->pluck('id');
        $ugcGeojson['properties']['ugc_tracks'] = $this->ugc_tracks->pluck('id');

        return $ugcGeojson;
    }
}
