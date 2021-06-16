<?php

namespace App\Nova;

use Davidpiesse\NovaToggle\Toggle;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Robertboes\NovaSliderField\NovaSliderField;
use Yna\NovaSwatches\Swatches;


class App extends Resource
{

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\App::class;

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
    ];

    public static function group()
    {
        return __('Editorial Content');
    }

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
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideWhenCreating()->hideWhenUpdating(),
            new Panel('App', $this->app_panel()),
            new Panel('Map', $this->map_panel()),
            new Panel('Theme', $this->theme_panel()),
            new Panel('Options', $this->option_panel()),
            new Panel('Table', $this->table_panel()),
            new Panel('Routing', $this->routing_panel()),
        ];
    }

    protected function app_panel()
    {
        return [
            Text::make(__('App Id'), 'app_id'),
            Text::make(__('Name'), 'name')->sortable(),
            Text::make(__('Customer Name'), 'customerName')->sortable(),
        ];
    }

    protected function map_panel()
    {
        return [
            NovaSliderField::make(__('Max Zoom'), 'maxZoom')->min(5)->max(19)->default(16)->onlyOnForms(),
            NovaSliderField::make(__('Min Zoom'), 'minZoom')->min(5)->max(19)->default(12)->onlyOnForms(),
            NovaSliderField::make(__('Def Zoom'), 'defZoom')->min(5)->max(19)->default(12)->onlyOnForms(),

            Number::make(__('Max Zoom'), 'maxZoom')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Min Zoom'), 'minZoom')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Def Zoom'), 'defZoom')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
        ];
    }

    protected function theme_panel()
    {
        return [
            Select::make(__('Font Family Header'), 'fontFamilyHeader')->options([
                'Helvetica' => ['label' => 'Helvetica'],
                'Lato' => ['label' => 'Lato'],
                'Merriweather' => ['label' => 'Merriweather'],
                'Montserrat' => ['label' => 'Montserrat'],
                'Montserrat Light' => ['label' => 'Montserrat Light'],
                'Noto Sans' => ['label' => 'Noto Sans'],
                'Noto Serif' => ['label' => 'Noto Serif'],
                'Open Sans' => ['label' => 'Roboto'],
                'Roboto' => ['label' => 'Noto Serif'],
                'Roboto Slab' => ['label' => 'Roboto Slab'],
                'Sora' => ['label' => 'Sora'],
                'Source Sans Pro' => ['label' => 'Source Sans Pro']
            ])->default('Roboto Slab')->hideFromIndex(),

            Select::make(__('Font Family Content'), 'fontFamilyContent')->options([
                'Helvetica' => ['label' => 'Helvetica'],
                'Lato' => ['label' => 'Lato'],
                'Merriweather' => ['label' => 'Merriweather'],
                'Montserrat' => ['label' => 'Montserrat'],
                'Montserrat Light' => ['label' => 'Montserrat Light'],
                'Noto Sans' => ['label' => 'Noto Sans'],
                'Noto Serif' => ['label' => 'Noto Serif'],
                'Open Sans' => ['label' => 'Open Sans'],
                'Roboto' => ['label' => 'Roboto'],
                'Roboto Slab' => ['label' => 'Roboto Slab'],
                'Sora' => ['label' => 'Sora'],
                'Source Sans Pro' => ['label' => 'Source Sans Pro']
            ])->default('Roboto')->hideFromIndex(),

            Swatches::make(__('Default Feature Color'), 'defaultFeatureColor')->default('#de1b0d')->hideFromIndex(),
            Swatches::make(__('Primary'), 'primary')->default('#de1b0d')->hideFromIndex(),
        ];
    }

    protected function option_panel()
    {
        return [
            Select::make(__('Start Url'), 'startUrl')->options([
                '/main/explore' => 'Home',
                '/main/map' => 'Map',
            ])->default('/main/explore'),

            Toggle::make(__('Show Edit Link'), 'showEditLink')->trueValue('On')->falseValue('Off')->default(false)->onlyOnForms(),
            Toggle::make(__('Skip Route Index Download'), 'skipRouteIndexDownload')->trueValue('On')->falseValue('Off')->default(true)->onlyOnForms(),
            NovaSliderField::make(__('Poi Min Radius'), 'poiMinRadius')->min(0.1)->max(3.5)->default(0.5)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Poi Max Radius'), 'poiMaxRadius')->min(0.1)->max(3.5)->default(1.2)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Poi Icon Zoom'), 'poiIconZoom')->min(5)->max(19)->default(16)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Poi Icon Radius'), 'poiIconRadius')->min(0.1)->max(3.5)->default(1)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Poi Min Zoom'), 'poiMinZoom')->min(5)->max(19)->default(13)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Poi Label Min Zoom'), 'poiLabelMinZoom')->min(5)->max(19)->default(10.5)->interval(0.1)->onlyOnForms(),

            Toggle::make(__('Show Track Ref Label'), 'showTrackRefLabel')->trueValue('On')->falseValue('Off')->default(false)->hideFromIndex(),

            Number::make(__('Poi Min Radius'), 'poiMinRadius')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Poi Max Radius'), 'poiMaxRadius')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Poi Icon Zoom'), 'poiIconZoom')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Poi Icon Radius'), 'poiIconRadius')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Poi Min Zoom'), 'poiMinZoom')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Poi Label Min Zoom'), 'poiLabelMinZoom')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
        ];
    }

    protected function table_panel()
    {
        return [
            Toggle::make(__('Show GPX Download'), 'showGpxDownload')->trueValue('On')->falseValue('Off')->default(false)->hideFromIndex(),
            Toggle::make(__('Show KML Download'), 'showKmlDownload')->trueValue('On')->falseValue('Off')->default(false)->hideFromIndex(),
            Toggle::make(__('Show Related POI'), 'showRelatedPoi')->trueValue('On')->falseValue('Off')->default(false)->hideFromIndex(),
        ];
    }

    protected function routing_panel()
    {
        return [
            Toggle::make(__('Enable Routing'), 'enableRouting')->trueValue('On')->falseValue('Off')->default(false)->hideFromIndex(),
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
        return [];
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
