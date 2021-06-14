<?php

namespace App\Nova;

use Davidpiesse\NovaToggle\Toggle;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
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
        return __('Admin');
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

            Number::make(__('Max Zoom'), 'maxZoom')->hideWhenUpdating()->hideWhenCreating(),
            Number::make(__('Min Zoom'), 'minZoom')->hideWhenUpdating()->hideWhenCreating(),
            Number::make(__('Def Zoom'), 'defZoom')->hideWhenUpdating()->hideWhenCreating(),
        ];
    }

    protected function theme_panel()
    {
        return [
            /**todo implementare select per fonts**/
            Text::make(__('Font Family Header'), 'fontFamilyHeader')->default('Roboto Slab'),
            Text::make(__('Font Family Content'), 'fontFamilyContent')->default('Roboto'),
            Swatches::make(__('Default Feature Color'), 'defaultFeatureColor')->default('#de1b0d'),
            Swatches::make(__('Primary'), 'primary')->default('#de1b0d'),
        ];
    }

    protected function option_panel()
    {
        return [
            Text::make(__('Start Url'), 'startUrl')->default('/main/explore'),
            Toggle::make(__('Show Edit Link'), 'showEditLink')->trueValue('On')->falseValue('Off')->default(false)->onlyOnForms(),
            Toggle::make(__('Skip Route Index Download'), 'skipRouteIndexDownload')->trueValue('On')->falseValue('Off')->default(true)->onlyOnForms(),
            NovaSliderField::make(__('Poi Min Radius'), 'poiMinRadius')->min(0.1)->max(3.5)->default(0.5)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Poi Max Radius'), 'poiMaxRadius')->min(0.1)->max(3.5)->default(1.2)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Poi Icon Zoom'), 'poiIconZoom')->min(5)->max(19)->default(16)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Poi Icon Radius'), 'poiIconRadius')->min(0.1)->max(3.5)->default(1)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Poi Min Zoom'), 'poiMinZoom')->min(5)->max(19)->default(13)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Poi Label Min Zoom'), 'poiLabelMinZoom')->min(5)->max(19)->default(10.5)->interval(0.1)->onlyOnForms(),
            Toggle::make(__('Show Track Ref Label'), 'showTrackRefLabel')->trueValue('On')->falseValue('Off')->default(false),

            Number::make(__('Poi Min Radius'), 'poiMinRadius')->hideWhenUpdating()->hideWhenCreating(),
            Number::make(__('Poi Max Radius'), 'poiMaxRadius')->hideWhenUpdating()->hideWhenCreating(),
            Number::make(__('Poi Icon Zoom'), 'poiIconZoom')->hideWhenUpdating()->hideWhenCreating(),
            Number::make(__('Poi Icon Radius'), 'poiIconRadius')->hideWhenUpdating()->hideWhenCreating(),
            Number::make(__('Poi Min Zoom'), 'poiMinZoom')->hideWhenUpdating()->hideWhenCreating(),
            Number::make(__('Poi Label Min Zoom'), 'poiLabelMinZoom')->hideWhenUpdating()->hideWhenCreating(),
        ];
    }

    protected function table_panel()
    {
        return [
            Toggle::make(__('Show GPX Download'), 'showGpxDownload')->trueValue('On')->falseValue('Off')->default(false),
            Toggle::make(__('Show KML Download'), 'showKmlDownload')->trueValue('On')->falseValue('Off')->default(false),
            Toggle::make(__('Show Related POI'), 'showRelatedPoi')->trueValue('On')->falseValue('Off')->default(false),
        ];
    }

    protected function routing_panel()
    {
        return [
            Toggle::make(__('Enable Routing'), 'enableRouting')->trueValue('On')->falseValue('Off')->default(false),
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
