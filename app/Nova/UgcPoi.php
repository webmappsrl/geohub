<?php

namespace App\Nova;

use App\Nova\Filters\AppFilter;
use App\Nova\Filters\DateRange;
use App\Nova\Filters\UgcCreationDateFilter;
use App\Nova\Filters\UgcUserFilter;
use Laravel\Nova\Fields\Code;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Suenerds\NovaSearchableBelongsToFilter\NovaSearchableBelongsToFilter;
use Titasgailius\SearchRelations\SearchesRelations;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;

class UgcPoi extends Resource
{
    use SearchesRelations;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static string $model = \App\Models\UgcPoi::class;
    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';
    public static $search = [
        'name', 'raw_data->waypointtype'
    ];
    public static array $searchRelations = [
        'taxonomy_wheres' => ['name']
    ];

    public static function group()
    {
        return __('User Generated Content');
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        if ($request->user()->can('Admin')) {
            return $query;
        }
        if ($request->user()->apps->pluck('app_id')->contains('it.netseven.forestecasentinesi')) {
            return $query->whereIn('app_id', ['it.net7.parcoforestecasentinesi'])
                ->orWhereIn('app_id', $request->user()->apps->pluck('app_id')->toArray());
        }
        return $query->whereIn('app_id', $request->user()->apps->pluck('app_id')->toArray());
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function fields(Request $request): array
    {
        return [
            //            ID::make(__('ID'), 'id')->sortable(),
            Text::make(__('Name'), 'name')->sortable(),
            BelongsTo::make(__('Creator'), 'user', User::class),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            Text::make(__('App ID'), 'app_id')->sortable(),
            BelongsToMany::make(__('Taxonomy wheres'))->searchable(),
            Boolean::make(__('Has content'), function ($model) {
                return isset($model->raw_data);
            })->onlyOnIndex(),
            Boolean::make(__('Has gallery'), function ($model) {
                $gallery = $model->ugc_media;

                return count($gallery) > 0;
            })->onlyOnIndex(),
            Boolean::make(__('Has geometry'), function ($model) {
                return isset($model->geometry);
            })->onlyOnIndex(),
            Code::make(__('Form data'), function ($model) {
                $jsonRawData = json_decode($model->raw_data, true);
                unset($jsonRawData['position']);
                unset($jsonRawData['displayPosition']);
                unset($jsonRawData['city']);
                unset($jsonRawData['date']);
                unset($jsonRawData['nominatim']);
                $rawData = json_encode($jsonRawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                return  $rawData;
            })->onlyOnDetail()->language('json')->rules('json'),
            Code::make(__('Device data'), function ($model) {
                $jsonRawData = json_decode($model->raw_data, true);
                $jsonData['position'] = $jsonRawData['position'];
                $jsonData['displayPosition'] = $jsonRawData['displayPosition'];
                $jsonData['city'] = $jsonRawData['city'];
                $jsonData['date'] = $jsonRawData['date'];
                $rawData = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                return  $rawData;
            })->onlyOnDetail()->language('json')->rules('json'),
            Code::make(__('Nominatim'), function ($model) {
                $jsonData = json_decode($model->raw_data, true)['nominatim'];
                $rawData = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                return  $rawData;
            })->onlyOnDetail()->language('json')->rules('json'),
            Code::make(__('Raw data'), function ($model) {
                $rawData = json_encode(json_decode($model->raw_data, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                return  $rawData;
            })->onlyOnDetail()->language('json')->rules('json'),
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                    'related' => $model->getRelatedUgcGeojson()
                ];
            })->onlyOnDetail(),
            BelongsToMany::make(__('UGC Medias'), 'ugc_media'),
            Text::make(__('Poi Type'), function ($model) {
                if (isset($model->raw_data) && property_exists(json_decode($model->raw_data), 'waypointtype')) {
                    return json_decode($model->raw_data)->waypointtype;
                }
                return '';
            })->onlyOnIndex(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param Request $request
     *
     * @return array
     */
    public function cards(Request $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function filters(Request $request): array
    {
        return [
            (new NovaSearchableBelongsToFilter('User'))
                ->fieldAttribute('user')
                ->filterBy('user_id'),
            (new UgcCreationDateFilter()),
            (new AppFilter()),

        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function lenses(Request $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function actions(Request $request): array
    {
        return [];
    }
}
