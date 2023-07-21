<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Text;
use App\Nova\Filters\DateRange;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use App\Nova\Filters\UgcCreationDateFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;
use Titasgailius\SearchRelations\SearchesRelations;
use Suenerds\NovaSearchableBelongsToFilter\NovaSearchableBelongsToFilter;

class UgcTrack extends Resource
{
    use SearchesRelations;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static string $model = \App\Models\UgcTrack::class;
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
        'name'
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
            BelongsToMany::make(__('Taxonomy wheres')),
            Boolean::make(__('Has content'), function ($model) {
                return isset($model->raw_data) && $model->raw_data != '[]';
            })->onlyOnIndex(),
            Boolean::make(__('Has gallery'), function ($model) {
                $gallery = $model->ugc_media;

                return count($gallery) > 0;
            })->onlyOnIndex(),
            Boolean::make(__('Has geometry'), function ($model) {
                return isset($model->geometry);
            })->onlyOnIndex(),
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                    'related' => $model->getRelatedUgcGeojson()
                ];
            })->onlyOnDetail(),
            BelongsToMany::make(__('UGC Medias'), 'ugc_media'),
            Code::Make(__('metadata'), 'metadata')->language('json')->rules('nullable', 'json')->help(
                'metadata of track'
            )->onlyOnDetail(),
            Text::make(__('Raw data'), function ($model) {
                $rawData = json_decode($model->raw_data, true);
                $result = [];

                foreach ($rawData as $key => $value) {
                    $result[] = $key . ' = ' . json_encode($value);
                }

                return join('<br>', $result);
            })->onlyOnDetail()->asHtml(),
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
            (new UgcCreationDateFilter)
            // new DateRange('created_at'),
            // (new NovaSearchableBelongsToFilter('Author'))
            //     ->fieldAttribute('user')
            //     ->filterBy('user_id')

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
