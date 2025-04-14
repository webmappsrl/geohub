<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\UgcPoi;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UgcResource;

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
