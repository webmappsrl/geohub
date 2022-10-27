<?php

namespace App\Nova\Actions;

use App\Models\EcTrack;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\LaravelNovaExcel\Actions\DownloadExcel;

class DownloadExcelEcTrackAction extends DownloadExcel implements WithMapping
{

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'name',
            'geohub_backend',
            'geohub_backend_edit',
            'geohub_frontend',
            'public_app_link',
            'description',
            'description_it',
            'description_en',
            'description_fr',
            'excerpt',
            'source',
            'distance_comp',
            'user_id',
            'feature_image',
            'audio',
            'distance',
            'ascent',
            'descent',
            'ele_from',
            'ele_to',
            'ele_min',
            'ele_max',
            'duration_forward',
            'duration_backward',
            'difficulty',
            'slope',
            'mbtiles',
            'elevation_chart_image',
            'out_source_feature_id',
            'from',
            'ref',
            'to',
            'cai_scale',
            'related_url',
            'not_accessible',
            'not_accessible_message',
            'image_gallery',
            'activity',
            'theme',
            'where',
            'when',
            'target',
        ];
    }
    
    /**
     * @param EcTrack $track
     *
     * @return array
     */
    public function map($track): array
    {
        $geohub_backend = '';
        $geohub_frontend = '';
        $featureImage = '';
        $image_gallery = '';
        $activities = '';
        $themes = '';
        $wheres = '';
        $whens = '';
        $targets = '';

        $geohub_backend = url('/').'/resources/ec-tracks/'. $track->id;
        $geohub_frontend = url('/').'/track/'. $track->id;
        if($track->featureImage) {
            if (strpos($track->featureImage->url,'ecmedia')){
                $featureImage = $track->featureImage->url;
            } else {
                $featureImage = Storage::disk('public')->url($track->featureImage->url);
            }
        }
        if ($track->EcMedia) {
            $image_gallery = implode(',',$track->EcMedia->pluck('url')->toArray());
        }
        if ($track->taxonomyActivities) {
            $activities = implode(',',$track->taxonomyActivities->pluck('name')->toArray());
        }
        if ($track->taxonomyThemes) {
            $themes = implode(',',$track->taxonomyThemes->pluck('name')->toArray());
        }
        if ($track->taxonomyWheres) {
            $wheres = implode(',',$track->taxonomyWheres->pluck('name')->toArray());
        }
        if ($track->taxonomyWhens) {
            $whens = implode(',',$track->taxonomyWhens->pluck('name')->toArray());
        }
        if ($track->taxonomyTargets) {
            $targets = implode(',',$track->taxonomyTargets->pluck('name')->toArray());   
        }

        $track = (object) $this->setOutSourceValue($track);

        $description_it = isset($track->description['it'])?$track->description['it']:'';
        $description_en = isset($track->description['en'])?$track->description['en']:'';
        $description_fr = isset($track->description['fr'])?$track->description['fr']:'';

        $geohub_backend_edit = "https://geohub.webmapp.it/resources/ec-tracks/$track->id/edit";

        $user = auth()->user();
        $public_app_link = '';
        if (!empty($user->apps)) {
            $app_id = $user->apps[0]->id;
            $public_app_link = "https://$app_id.app.geohub.webmapp.it/#/map?track=$track->id";
        }

        return [
            $track->id,
            $track->created_at,
            $track->updated_at,
            $track->name,
            $geohub_backend,
            $geohub_backend_edit,
            $geohub_frontend,
            $public_app_link,
            $track->description,
            $description_it, 
            $description_en, 
            $description_fr,
            $track->excerpt,
            $track->source,
            $track->distance_comp,
            $track->user_id,
            $featureImage,
            $track->audio,
            $track->distance,
            $track->ascent,
            $track->descent,
            $track->ele_from,
            $track->ele_to,
            $track->ele_min,
            $track->ele_max,
            $track->duration_forward,
            $track->duration_backward,
            $track->difficulty,
            $track->slope,
            $track->mbtiles,
            $track->elevation_chart_image,
            $track->out_source_feature_id,
            $track->from,
            $track->ref,
            $track->to,
            $track->cai_scale,
            $track->related_url,
            $track->not_accessible,
            $track->not_accessible_message,
            $image_gallery,
            $activities,
            $themes,
            $wheres,
            $whens,
            $targets,
        ];
    }

    private function setOutSourceValue($track):array {
        $array = $track->toArray();
        if(isset($track->out_source_feature_id)) {
            $keys = [
                'description',
                'excerpt',
                'distance',
                'ascent',
                'descent',
                'ele_min',
                'ele_max',
                'ele_from',
                'ele_to',
                'duration_forward',
                'duration_backward',
                'ref',
                'difficulty',
                'cai_scale',
            ];
            foreach ($keys as $key) {
                $array= $this->setOutSourceSingleValue($array,$key,$track);
            }
        }
        return $array;
    }

    private function setOutSourceSingleValue($array,$varname,$track):array {
        if($this->isReallyEmpty($array[$varname])) {
            if(isset($track->outSourceTrack->tags[$varname])) {
                $array[$varname] = $track->outSourceTrack->tags[$varname];
            }
        }
        return $array;
    }

    private function isReallyEmpty($val): bool {
        if(is_null($val)) {
            return true;
        }
        if(empty($val)) {
            return true;
        }
        if(is_array($val)) {
            if(count($val)==0) {
                return true;
            }
            foreach($val as $lang => $cont) {
                if(!empty($cont)) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }
}
