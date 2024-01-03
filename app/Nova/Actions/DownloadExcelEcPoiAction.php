<?php

namespace App\Nova\Actions;

use App\Models\EcPoi;
use App\Models\OutSourcePoi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\LaravelNovaExcel\Actions\DownloadExcel;

class DownloadExcelEcPoiAction extends DownloadExcel implements WithMapping
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
            'osmid',
            'name',
            'name_it',
            'name_en',
            'name_fr',
            'geohub_backend',
            'geohub_backend_edit',
            'public_app_link',
            'lat',
            'lng',
            'description',
            'description_it',
            'description_en',
            'description_fr',
            'excerpt',
            'feature_image',
            'contact_phone',
            'contact_email',
            'audio',
            'related_url',
            'ele',
            'addr_street',
            'addr_housenumber',
            'addr_postcode',
            'addr_locality',
            'opening_hours',
            'out_source_feature_id',
            'capacity',
            'stars',
            'color',
            'icon',
            'code',
            'noDetails',
            'noInteraction',
            'accessibility_validity_date',
            'accessibility_pdf',
            'access_mobility_check',
            'access_mobility_level',
            'access_mobility_description',
            'access_hearing_check',
            'access_hearing_level',
            'access_hearing_description',
            'access_vision_check',
            'access_vision_level',
            'access_vision_description',
            'access_cognitive_check',
            'access_cognitive_level',
            'access_cognitive_description',
            'access_food_check',
            'access_food_description',
            'reachability_by_bike_check',
            'reachability_by_bike_description',
            'reachability_on_foot_check',
            'reachability_on_foot_description',
            'reachability_by_car_check',
            'reachability_by_car_description',
            'reachability_by_public_transportation_check',
            'reachability_by_public_transportation_description',
            'zindex',
            'addr_complete',
            'image_gallery',
            'poi_type',
            'wheres'
        ];
    }

    /**
     * @param EcPoi $poi
     *
     * @return array
     */
    public function map($poi): array
    {
        $featureImage = '';
        $image_gallery = '';
        $poi_type = '';
        $lng = 0;
        $lat = 0;

        if ($poi->geometry) {
            $lng = DB::select("SELECT ST_X(ST_AsText('$poi->geometry')) As wkt")[0]->wkt;
            $lat = DB::select("SELECT ST_Y(ST_AsText('$poi->geometry')) As wkt")[0]->wkt;
        }
        $geohub_backend = url('/') . '/resources/ec-pois/' . $poi->id;
        if($poi->featureImage) {
            if (strpos($poi->featureImage->url, 'ecmedia')) {
                $featureImage = $poi->featureImage->url;
            } else {
                $featureImage = Storage::disk('public')->url($poi->featureImage->url);
            }
        }
        if ($poi->EcMedia) {
            $image_gallery = implode(',', $poi->EcMedia->pluck('url')->toArray());
        }
        if ($poi->taxonomyPoiTypes) {
            $poi_type = implode(',', $poi->taxonomyPoiTypes->pluck('name')->toArray());
        }

        if ($poi->taxonomyWheres) {
            $wheres = implode(',', $poi->taxonomyWheres->pluck('name')->toArray());
        }

        $poi = (object) $this->setOutSourceValue($poi);

        $description_it = isset($poi->description['it']) ? $poi->description['it'] : '';
        $description_en = isset($poi->description['en']) ? $poi->description['en'] : '';
        $description_fr = isset($poi->description['fr']) ? $poi->description['fr'] : '';

        $name_it = isset($poi->name['it']) ? $poi->name['it'] : '';
        $name_en = isset($poi->name['en']) ? $poi->name['en'] : '';
        $name_fr = isset($poi->name['fr']) ? $poi->name['fr'] : '';

        $geohub_backend_edit = "https://geohub.webmapp.it/resources/ec-pois/$poi->id/edit";

        $user = auth()->user();
        $public_app_link = '';
        if ($user->id == 19839) {
            $out_source_id = $poi->out_source_feature_id;
            $out_source_feature = OutSourcePoi::find($out_source_id);
            $public_app_link = "http://ir.j.webmapp.it/#/main/details/$out_source_feature->source_id";
        }

        return [
            $poi->id,
            $poi->created_at,
            $poi->updated_at,
            $poi->osmid,
            $poi->name,
            $name_it,
            $name_en,
            $name_fr,
            $geohub_backend,
            $geohub_backend_edit,
            $public_app_link,
            $lat,
            $lng,
            $poi->description,
            $description_it,
            $description_en,
            $description_fr,
            $poi->excerpt,
            $featureImage,
            $poi->contact_phone,
            $poi->contact_email,
            $poi->audio,
            $poi->related_url,
            $poi->ele,
            $poi->addr_street,
            $poi->addr_housenumber,
            $poi->addr_postcode,
            $poi->addr_locality,
            $poi->opening_hours,
            $poi->out_source_feature_id,
            $poi->capacity,
            $poi->stars,
            $poi->color,
            $poi->icon,
            $poi->code,
            $poi->noDetails,
            $poi->noInteraction,
            $poi->accessibility_validity_date,
            $poi->accessibility_pdf,
            $poi->access_mobility_check,
            $poi->access_mobility_level,
            $poi->access_mobility_description,
            $poi->access_hearing_check,
            $poi->access_hearing_level,
            $poi->access_hearing_description,
            $poi->access_vision_check,
            $poi->access_vision_level,
            $poi->access_vision_description,
            $poi->access_cognitive_check,
            $poi->access_cognitive_level,
            $poi->access_cognitive_description,
            $poi->access_food_check,
            $poi->access_food_description,
            $poi->reachability_by_bike_check,
            $poi->reachability_by_bike_description,
            $poi->reachability_on_foot_check,
            $poi->reachability_on_foot_description,
            $poi->reachability_by_car_check,
            $poi->reachability_by_car_description,
            $poi->reachability_by_public_transportation_check,
            $poi->reachability_by_public_transportation_description,
            $poi->zindex,
            $poi->addr_complete,
            $image_gallery,
            $poi_type,
            $wheres
        ];
    }

    private function setOutSourceValue($poi): array
    {
        $array = $poi->toArray();
        if(isset($poi->out_source_feature_id)) {
            $keys = [
                'description',
                'excerpt',
                'feature_image',
                'contact_phone',
                'contact_email',
                'audio',
                'related_url',
                'ele',
                'addr_street',
                'addr_housenumber',
                'addr_postcode',
                'addr_locality',
                'opening_hours',
            ];
            foreach ($keys as $key) {
                $array = $this->setOutSourceSingleValue($array, $key, $poi);
            }
        }
        return $array;
    }

    private function setOutSourceSingleValue($array, $varname, $poi): array
    {
        if($this->isReallyEmpty($array[$varname])) {
            if(isset($poi->outSourcePoi->tags[$varname])) {
                $array[$varname] = $poi->outSourcePoi->tags[$varname];
            }
        }
        return $array;
    }

    private function isReallyEmpty($val): bool
    {
        if(is_null($val)) {
            return true;
        }
        if(empty($val)) {
            return true;
        }
        if(is_array($val)) {
            if(count($val) == 0) {
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
