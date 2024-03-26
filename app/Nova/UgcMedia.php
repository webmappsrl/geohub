<?php

namespace App\Nova;

use App\Nova\Actions\DownloadGeojsonUgcMediaAction;
use App\Nova\Filters\AppFilter;
use App\Nova\Filters\DateRange;
use App\Nova\Filters\UgcCreationDateFilter;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Suenerds\NovaSearchableBelongsToFilter\NovaSearchableBelongsToFilter;
use Titasgailius\SearchRelations\SearchesRelations;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;
use Wm\MapPointNova3\MapPointNova3;

class UgcMedia extends Resource
{
    use SearchesRelations;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static string $model = \App\Models\UgcMedia::class;
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
        'name', 'app_id'
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
            Image::make('Image', 'relative_url')->disk('public'),
            Text::make(__('Name'), function ($model) {
                $relativeUrl = $model->relative_url;

                return last(explode('/', $relativeUrl));
            }),
            BelongsTo::make(__('Creator'), 'user', User::class)->onlyOnIndex(),
            BelongsTo::make(__('Creator'), 'author', User::class)->searchable()->onlyOnDetail(),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            Text::make(__('App ID'), 'app_id')->sortable(),

            Boolean::make(__('Has geometry'), function ($model) {
                return isset($model->geometry);
            })->onlyOnIndex(),
            BelongsToMany::make(__('Taxonomy wheres')),
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                    'related' => $model->getRelatedUgcGeojson()
                ];
            })->onlyOnDetail(),
            MapPointNova3::make(__('Map'), 'geometry')->withMeta([
                'center' => ["51", "4"],
                'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                'minZoom' => 7,
                'maxZoom' => 16,
            ])->onlyOnForms(),
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
            (new NovaSearchableBelongsToFilter('Creator'))
                ->fieldAttribute('user')
                ->filterBy('user_id'),
            (new UgcCreationDateFilter),
            (new AppFilter),
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
