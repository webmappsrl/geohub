<?php

namespace App\Http\Resources;

class UgcTrackResource extends UgcResource
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

        $mediaIds = [];
        $ugcMedia = $this->ugc_media;
        if (count($ugcMedia) > 0) {
            foreach ($ugcMedia as $media) {
                $mediaIds[] = $media->id;
            }
        }

        $ugcGeojson['properties']['media_ids'] = $mediaIds;

        return $ugcGeojson;
    }
}
