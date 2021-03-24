<?php

namespace App\Nova;

use App\Nova\Filters\DateRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Webmapp\RawGallery\RawGallery;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;

class UserGeneratedData extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\UserGeneratedData::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name',
        'app_id'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make(__('Name'), 'name')->sortable(),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            Text::make(__('App ID'), 'app_id')->sortable(),
            Boolean::make(__('Has content'), function ($model) {
                return isset($model->raw_data);
            })->onlyOnIndex(),
            Boolean::make(__('Has gallery'), function ($model) {
                return isset($model->raw_gallery);
            })->onlyOnIndex(),
            Boolean::make(__('Has geometry'), function ($model) {
                return isset($model->geometry);
            })->onlyOnIndex(),
            Text::make(__('Raw data'), function ($model) {
                $rawData = json_decode($model->raw_data, true);
                $result = [];

                foreach ($rawData as $key => $value) {
                    $result[] = $key . ' = ' . json_encode($value);
                }

                return join('<br>', $result);
            })->onlyOnDetail()->asHtml(),
            RawGallery::make(__('Gallery'), function ($model) {
                return json_decode($model->raw_gallery, true);
            })->onlyOnDetail(),
            WmEmbedmapsField::make(__('Map'), function ($model) {
                $geom = \App\Models\UserGeneratedData::find($model->id)
                    ->select(
                        DB::raw('ST_AsGeoJSON(geometry) as geom')
                    )
                    ->first()
                    ->geom;
                $feature = [
                    "type" => "Feature",
                    "properties" => [],
                    "geometry" => json_decode($geom, true)
                ];

                return json_encode($feature);
            })->onlyOnDetail()
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [
            new DateRange('created_at')
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
