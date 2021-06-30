<?php

namespace App\Nova;

use Davidpiesse\NovaToggle\Toggle;
use Illuminate\Auth\Access\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Robertboes\NovaSliderField\NovaSliderField;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;
use Yna\NovaSwatches\Swatches;


class App extends Resource
{
    public static function indexQuery(NovaRequest $request, $query)
    {
        $user = \App\Models\User::getEmulatedUser();
        if ($user->hasRole('Admin')) {
            $query = parent::indexQuery($request, $query);
            return $query;
        } else {
            $query = parent::indexQuery($request, $query);
            return $query->where('user_id', $user->id);
        }
    }

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
            Heading::make('<div class="flex items-center" style="text-align: right;display: block;">
   <button type="submit" class="button-fixed btn btn-default btn-primary inline-flex items-center relative" dusk="create-button">
        Create App
      </button>
      </div>')->asHtml()->showOnCreating()->hideWhenUpdating()->hideFromDetail(),


            Heading::make('<div class="flex items-center" style="text-align: right;display: block;">
      <button type="submit" class="button-fixed btn btn-default btn-primary inline-flex items-center relative" dusk="update-button">
        Update App
      </span>
      </button>
      </div>')->asHtml()->showOnUpdating()->hideWhenCreating()->hideFromDetail(),
            ID::make(__('ID'), 'id')->sortable(),
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideWhenCreating()->hideWhenUpdating(),
            new Panel('App', $this->app_panel()),
            new Panel('Map', $this->map_panel()),
            new Panel('Theme', $this->theme_panel()),
            new Panel('Options', $this->option_panel()),
            new Panel('Table', $this->table_panel()),
            new Panel('Routing', $this->routing_panel()),
            new Panel('API', $this->api_panel()),
            new Panel ('Maps', $this->maps_panel()),
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

    protected function api_panel()
    {
        return [
            Text::make(__('API List'), function () {
                return '<a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/config.json" target="_blank">Config</a>
                <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/activity.json" target="_blank">Activity</a>
                    <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/theme.json" target="_blank">Theme</a>
                    <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/when.json" target="_blank">When</a>
                    <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/where.json" target="_blank">Where</a>
                    <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/who.json" target="_blank">Target</a>';
            })->asHtml()->onlyOnDetail(),
            Text::make(__('API List (Tracks)'), function ($tracks) {
                $tracks = \App\Models\EcTrack::where('user_id', $this->model()->user_id)->get();
                $html = '';
                foreach ($tracks as $track) {
                    $html .= '<div style="display:flex;margin-top:15px">';
                    $html .= '<div class="col-3">';
                    $html .= '<a class="btn btn-default btn-primary mx-2" href="/api/app/elbrus/' . $this->model()->id . '/geojson/ec_track_' . $track->id . '.geojson">' . $track->name . '</a>';
                    $html .= '</div>';
                    $html .= '<div class="col-3">';
                    $html .= '<a class="btn btn-default btn-secondary mx-2" href="/resources/ec-tracks/' . $track->id . '/edit">Modifica Track</a>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
                return $html;
            })->asHtml()->onlyOnDetail(),


        ];

    }

    protected function maps_panel()
    {
        return [
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                ];
            })->onlyOnDetail(),
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
