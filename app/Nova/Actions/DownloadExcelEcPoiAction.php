<?php

namespace App\Nova\Actions;

use App\Models\EcPoi;
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
            'name',
            'geohub_backend',
            'description',
            'excerpt',
            'user_id',
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
        
        $geohub_backend = url('/').'/resources/ec-pois/'. $poi->id;
        if($poi->featureImage) {
            if (strpos($poi->featureImage->url,'ecmedia')){
                $featureImage = $poi->featureImage->url;
            } else {
                $featureImage = Storage::disk('public')->url($poi->featureImage->url);
            }
        }
        if ($poi->EcMedia) {
            $image_gallery = implode(',',$poi->EcMedia->pluck('url')->toArray());
        }
        if ($poi->taxonomyPoiTypes) {
            $poi_type = implode(',',$poi->taxonomyPoiTypes->pluck('name')->toArray());
        }

        return [
            $poi->id,
            $poi->created_at,
            $poi->updated_at,
            $poi->name,
            $geohub_backend,
            $poi->description,
            $poi->excerpt,
            $poi->user_id,
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
        ];
    }
}
