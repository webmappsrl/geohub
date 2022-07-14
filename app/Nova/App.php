<?php

namespace App\Nova;

use App\Helpers\NovaCurrentResourceActionHelper;
use App\Rules\AppImagesRule;
use Davidpiesse\NovaToggle\Toggle;
use Eminiarts\Tabs\ActionsInTabs;
use Eminiarts\Tabs\Tabs;
use Eminiarts\Tabs\TabsOnEdit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\BooleanGroup;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Nova\Multiselect\Multiselect;
use NovaAttachMany\AttachMany;
use Robertboes\NovaSliderField\NovaSliderField;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;
use Yna\NovaSwatches\Swatches;
use Kraftbit\NovaTinymce5Editor\NovaTinymce5Editor;

/**
 * Refers to official CONFIG documentation: https://github.com/webmappsrl/wm-app/blob/develop/docs/config/config.md
 * Config SECTIONS:
 * APP
 * AUTH
 * EVENTS
 * FILTERS
 * GEOLOCATION
 * HOME
 * INCLUDE
 * LANGUAGES
 * LOAD
 * MAP
 * OFFLINE
 * OPTIONS
 * PAGES
 * REPORTS
 * ROUTING
 * SHARE
 * STRINGS
 * TABLES
 * THEME
 */
class App extends Resource
{

    use TabsOnEdit;

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
     * @param Request $request
     *
     * @return array
     */
    public function fields(Request $request): array
    {
        // Default:
        return [
            ID::make(__('ID'), 'id')->sortable(),
            BelongsTo::make('Author', 'author', User::class)->sortable(),
            Text::make('API type', 'api')->sortable(),
            Text::make('Name')->sortable(),
            Text::make('Customer Name'),
            AttachMany::make('TaxonomyThemes'),
        ];
    }

    public function fieldsForIndex(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            BelongsTo::make('Author', 'author', User::class)->sortable(),
            Text::make('API type', 'api')->sortable(),
            Text::make('Name')->sortable(),
            Text::make('Customer Name'),
        ];
    }



    public function fieldsForDetail(Request $request)
    {
        return [
            (new Tabs("APP Details: {$this->name} ({$this->id})", $this->sections()))->withToolbar(),
        ];
    }

    public function fieldsForCreate(Request $request)
    {
        $availableLanguages = is_null($this->model()->available_languages) ? [] : json_decode($this->model()->available_languages, true);

        return [
            Select::make(__('Api API'), 'api')->options(
                [
                    'elbrus' => 'Elbrus',
                    'webmapp' => 'WebMapp',
                    'webapp' => 'WebApp',
                ]
            )->required(),
            Text::make(__('App Id'), 'app_id')->required(),
            Text::make(__('Name'), 'name')->sortable()->required(),
            Text::make(__('Customer Name'), 'customer_name')->sortable()->required(),
            Select::make(__('Default Language'), 'default_language')->hideFromIndex()->options([
                'en' => 'English',
                'it' => 'Italiano',
            ])->displayUsingLabels()->required(),
            Multiselect::make(__('Available Languages'), 'available_languages')->hideFromIndex()->options([
                'en' => 'English',
                'it' => 'Italiano',
            ], $availableLanguages)
        ];
    }
    public function fieldsForUpdate(Request $request)
    {

        return [
            (new Tabs("APP Details: {$this->name} ({$this->id})", $this->sections())),
        ];
    }

    public function sections()
    {
        return [
            'APP' => $this->app_tab(),
            'HOME' => $this->home_tab(),
            'PROJECT' => $this->project_tab(),
            'AUTH' => $this->auth_tab(),
            'OFFLINE' => $this->offline_tab(),
            'GEOLOCATION' => $this->geolocation_tab(),
            'ICONS' => $this->icons_tab(),
            'LANGUAGES' => $this->languages_tab(),
            'MAP' => $this->map_tab(),
            'OPTIONS' => $this->options_tab(),
            'POIS' => $this->pois_tab(),
            'ROUTING' => $this->routing_tab(),
            'TABLE' => $this->table_tab(),
            'THEME' => $this->theme_tab(),
            'LAYERS' => $this->layers_tab(),
        ];
    }

    protected function app_tab(): array
    {
        return [
            Select::make(__('API type'), 'api')->options(
                [
                    'elbrus' => 'Elbrus',
                    'webmapp' => 'WebMapp',
                    'webapp' => 'WebApp',
                ]
            )->required(),
            Text::make(__('App Id'), 'app_id')->required(),
            Text::make(__('Name'), 'name')->sortable()->required(),
            Text::make(__('Customer Name'), 'customer_name')->sortable()->required(),
            Text::make(__('Play Store link (android)'), 'android_store_link'),
            Text::make(__('App Store link (iOS)'), 'ios_store_link'),
            BelongsTo::make('Author', 'author', User::class)
                ->searchable()
                ->nullable()
                ->canSee(function ($request) {
                    return $request->user()->can('Admin', $this);
                }),
            AttachMany::make('TaxonomyThemes'),
            Text::make('Themes', function () {
                if ($this->taxonomyThemes()->count() > 0) {
                    return implode(',', $this->taxonomyThemes()->pluck('name')->toArray());
                }
                return 'No Themes';
            }),
        ];
    }

    protected function home_tab(): array
    {
        return [
            Code::Make('Config Home')->language('json')->rules('json')->default('{"HOME": []}')->help(
                view('layers', ['layers' => $this->layers])->render()
            )
        ];
    }

    protected function project_tab(): array
    {
        return [
            NovaTinymce5Editor::make('Page Project', 'page_project'),

        ];
    }
    protected function languages_tab(): array
    {

        $availableLanguages = is_null($this->model()->available_languages) ? [] : json_decode($this->model()->available_languages, true);

        return [
            Select::make(__('Default Language'), 'default_language')->hideFromIndex()->options([
                'en' => 'English',
                'it' => 'Italiano',
            ])->displayUsingLabels(),
            Multiselect::make(__('Available Languages'), 'available_languages')->hideFromIndex()->options([
                'en' => 'English',
                'it' => 'Italiano',
            ], $availableLanguages)
        ];
    }

    protected function map_tab(): array
    {
        return [
            Multiselect::make(__('Tiles'), 'tiles')->options([
                'https://api.webmapp.it/tiles/{z}/{x}/{y}.png' => 'webmapp',
                'https://api.maptiler.com/tiles/satellite/{z}/{x}/{y}.jpg?key=0Z7ou7nfFFXipdDXHChf' => 'satellite',
            ], ['https://api.webmapp.it/tiles/{z}/{x}/{y}.png'])->help(__('seleziona quali tile layer verranno utilizzati dalla app, l\' lordine è il medesimo di inserimento quindi l\'ultimo inserito sarà quello visibile per primo')),
            NovaSliderField::make(__('Max Zoom'), 'map_max_zoom')
                ->min(5)
                ->max(19)
                ->default(16)
                ->onlyOnForms(),
            NovaSliderField::make(__('Min Zoom'), 'map_min_zoom')
                ->min(5)
                ->max(19)
                ->default(12)
                ->onlyOnForms(),
            NovaSliderField::make(__('Def Zoom'), 'map_def_zoom')
                ->min(5)
                ->max(19)
                ->interval(0.1)
                ->default(12)
                ->onlyOnForms(),
            Text::make(__('Bounding BOX'), 'map_bbox')
                ->nullable()
                ->onlyOnForms()
                ->rules([
                    function ($attribute, $value, $fail) {
                        $decoded = json_decode($value);
                        if (is_array($decoded) == false) {
                            $fail('The ' . $attribute . ' is invalid. follow the example [9.9456,43.9116,11.3524,45.0186]');
                        }
                    }
                ]),

            Number::make(__('Max Zoom'), 'map_max_zoom')->onlyOnDetail(),
            Number::make(__('Min Zoom'), 'minZoom')->onlyOnDetail(),
            Number::make(__('Def Zoom'), 'defZoom')->onlyOnDetail(),
            Text::make(__('Bounding BOX'), 'bbox')->onlyOnDetail(),
        ];
    }

    protected function theme_tab(): array
    {
        $fontsOptions = [
            'Helvetica' => ['label' => 'Helvetica'],
            'Inter' => ['label' => 'Inter'],
            'Lato' => ['label' => 'Lato'],
            'Merriweather' => ['label' => 'Merriweather'],
            'Montserrat' => ['label' => 'Montserrat'],
            'Montserrat Light' => ['label' => 'Montserrat Light'],
            'Monrope' => ['label' => 'Monrope'],
            'Noto Sans' => ['label' => 'Noto Sans'],
            'Noto Serif' => ['label' => 'Noto Serif'],
            'Open Sans' => ['label' => 'Roboto'],
            'Roboto' => ['label' => 'Noto Serif'],
            'Roboto Slab' => ['label' => 'Roboto Slab'],
            'Sora' => ['label' => 'Sora'],
            'Source Sans Pro' => ['label' => 'Source Sans Pro']
        ];

        return [
            Select::make(__('Font Family Header'), 'font_family_header')
                ->options($fontsOptions)
                ->default('Roboto Slab')
                ->hideFromIndex(),
            Select::make(__('Font Family Content'), 'font_family_content')
                ->options($fontsOptions)
                ->default('Roboto')
                ->hideFromIndex(),
            Swatches::make(__('Default Feature Color'), 'default_feature_color')
                ->default('#de1b0d')
                ->hideFromIndex(),
            Swatches::make(__('Primary color'), 'primary_color')
                ->default('#de1b0d')
                ->hideFromIndex(),
        ];
    }

    protected function options_tab(): array
    {
        return [
            Select::make(__('Start Url'), 'start_url')
                ->options([
                    '/main/explore' => 'Home',
                    '/main/map' => 'Map',
                ])
                ->default('/main/explore'),
            Toggle::make(__('Show Edit Link'), 'show_edit_link')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->onlyOnForms(),
            Toggle::make(__('Skip Route Index Download'), 'skip_route_index_download')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->onlyOnForms(),
            Toggle::make(__('Show Track Ref Label'), 'show_track_ref_label')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),

        ];
    }

    protected function pois_tab(): array
    {
        return [
            Toggle::make(__('Show Pois Layer on APP'), 'app_pois_api_layer')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
            NovaSliderField::make(__('Poi Min Radius'), 'poi_min_radius')
                ->min(0.1)
                ->max(3.5)
                ->default(0.5)
                ->interval(0.1)
                ->onlyOnForms(),
            NovaSliderField::make(__('Poi Max Radius'), 'poi_max_radius')
                ->min(0.1)
                ->max(3.5)
                ->default(1.2)
                ->interval(0.1)
                ->onlyOnForms(),
            NovaSliderField::make(__('Poi Icon Zoom'), 'poi_icon_zoom')
                ->min(5)
                ->max(19)
                ->default(16)
                ->interval(0.1)
                ->onlyOnForms(),
            NovaSliderField::make(__('Poi Icon Radius'), 'poi_icon_radius')
                ->min(0.1)
                ->max(3.5)
                ->default(1.5)
                ->interval(0.1)
                ->onlyOnForms(),
            NovaSliderField::make(__('Poi Min Zoom'), 'poi_min_zoom')
                ->min(5)
                ->max(19)
                ->default(13)
                ->interval(0.1)
                ->onlyOnForms(),
            NovaSliderField::make(__('Poi Label Min Zoom'), 'poi_label_min_zoom')
                ->min(5)
                ->max(19)
                ->default(10.5)
                ->interval(0.1)
                ->onlyOnForms(),

            Number::make(__('Poi Min Radius'), 'poi_min_radius')->onlyOnDetail(),
            Number::make(__('Poi Max Radius'), 'poi_max_radius')->onlyOnDetail(),
            Number::make(__('Poi Icon Zoom'), 'poi_icon_zoom')->onlyOnDetail(),
            Number::make(__('Poi Icon Radius'), 'poi_icon_radius')->onlyOnDetail(),
            Number::make(__('Poi Min Zoom'), 'poi_min_zoom')->onlyOnDetail(),
            Number::make(__('Poi Label Min Zoom'), 'poi_label_min_zoom')->onlyOnDetail(),
            AttachMany::make('TaxonomyThemes'),
            Text::make('Themes', function () {
                if ($this->taxonomyThemes()->count() > 0) {
                    return implode(', ', $this->taxonomyThemes()->pluck('name')->toArray());
                }
                return 'No Themes';
            })->onlyOnDetail(),
        ];
    }

    protected function auth_tab(): array
    {
        return [
            Toggle::make(__('Show Auth at startup'), 'auth_show_at_startup')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
        ];
    }

    protected function table_tab(): array
    {
        return [
            Toggle::make(__('Show Related POI'), 'table_details_show_related_poi')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
            Toggle::make(__('Show Duration'), 'table_details_show_duration_forward')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Duration Backward'), 'table_details_show_duration_backward')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex()
                ->hideFromIndex(),
            Toggle::make(__('Show Distance'), 'table_details_show_distance')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Ascent'), 'table_details_show_ascent')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Descent'), 'table_details_show_descent')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Ele Max'), 'table_details_show_ele_max')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Ele Min'), 'table_details_show_ele_min')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Ele From'), 'table_details_show_ele_from')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Ele To'), 'table_details_show_ele_to')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Scale'), 'table_details_show_scale')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Cai Scale'), 'table_details_show_cai_scale')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Mtb Scale'), 'table_details_show_mtb_scale')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Ref'), 'table_details_show_ref')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex(),
            Toggle::make(__('Show Surface'), 'table_details_show_surface')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
            Toggle::make(__('Show GPX Download'), 'table_details_show_gpx_download')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
            Toggle::make(__('Show KML Download'), 'table_details_show_kml_download')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
            Toggle::make(__('Show Geojson Download'), 'table_details_show_geojson_download')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
            Toggle::make(__('Show Shapefile Download'), 'table_details_show_shapefile_download')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex()
        ];
    }

    protected function geolocation_tab(): array
    {
        return [
            Toggle::make(__('Enable UGC record'), 'geolocation_record_enable')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
        ];
    }

    protected function routing_tab(): array
    {
        return [
            Toggle::make(__('Enable Routing'), 'enable_routing')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
        ];
    }

    protected function overlays_tab(): array
    {
        return [
            Textarea::make(__('External overlays'), 'external_overlays')
                ->rows(10)
                ->hideFromIndex(),
        ];
    }

    protected function offline_tab(): array
    {
        return [
            Toggle::make(__('Enable Offline'), 'offline_enable')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
            Toggle::make(__('Force Auth'), 'offline_force_auth')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
            Toggle::make(__('Tracks on payment'), 'tracks_on_payment')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
        ];
    }

    protected function icons_tab(): array
    {
        return [
            Image::make(__('Icon'), 'icon')
                ->rules('image', 'mimes:png', 'dimensions:width=1024,height=1024')
                ->disk('public')
                ->path('api/app/' . $this->model()->id . '/resources')
                ->storeAs(function () {
                    return 'icon.png';
                })
                ->help(__('Required size is :widthx:heightpx', ['width' => 1024, 'height' => 1024]))
                ->hideFromIndex(),
            Image::make(__('Splash image'), 'splash')
                ->rules('image', 'mimes:png', 'dimensions:width=2732,height=2732')
                ->disk('public')
                ->path('api/app/' . $this->model()->id . '/resources')
                ->storeAs(function () {
                    return 'splash.png';
                })
                ->help(__('Required size is :widthx:heightpx', ['width' => 2732, 'height' => 2732]))
                ->hideFromIndex(),
            Image::make(__('Icon small'), 'icon_small')
                ->rules('image', 'mimes:png', 'dimensions:width=512,height=512')
                ->disk('public')
                ->path('api/app/' . $this->model()->id . '/resources')
                ->storeAs(function () {
                    return 'icon_small.png';
                })
                ->help(__('Required size is :widthx:heightpx', ['width' => 512, 'height' => 512]))
                ->hideFromIndex(),

            Image::make(__('Feature image'), 'feature_image')
                ->rules('image', 'mimes:png', 'dimensions:width=1024,height=500')
                ->disk('public')
                ->path('api/app/' . $this->model()->id . '/resources')
                ->storeAs(function () {
                    return 'feature_image.png';
                })
                ->help(__('Required size is :widthx:heightpx', ['width' => 1024, 'height' => 500]))
                ->hideFromIndex(),

            Image::make(__('Icon Notify'), 'icon_notify')
                ->rules('image', 'mimes:png', 'dimensions:ratio=1')
                ->disk('public')
                ->path('api/app/' . $this->model()->id . '/resources')
                ->storeAs(function () {
                    return 'icon_notify.png';
                })
                ->help(__('Required square png. Transparency is allowed and recommended for the background'))
                ->hideFromIndex(),

            Image::make(__('Logo Homepage'), 'logo_homepage')
                ->rules('image', 'mimes:svg')
                ->disk('public')
                ->path('api/app/' . $this->model()->id . '/resources')
                ->storeAs(function () {
                    return 'logo_homepage.svg';
                })
                ->help(__('Required svg image'))
                ->hideFromIndex(),
        ];
    }

    protected function api_tab(): array
    {
        return [
            Text::make(__('API List'), function () {
                return '<a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/config.json" target="_blank">Config</a>
                <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/activity.json" target="_blank">Activity</a>
                    <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/theme.json" target="_blank">Theme</a>
                    <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/when.json" target="_blank">When</a>
                    <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/where.json" target="_blank">Where</a>
                    <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/who.json" target="_blank">Target</a>
                    <a class="btn btn-default btn-primary" href="/api/app/elbrus/' . $this->model()->id . '/taxonomies/webmapp_category.json" target="_blank">Webmapp Category</a>';
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

    protected function maps_tab(): array
    {
        return [
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'features' => json_decode($model->getGeojson()),
                ];
            })->onlyOnDetail(),
        ];
    }

    protected function layers_tab(): array
    {
        return [
            // TODO: passare a hasMany ... attualmente ha un bug che non fa funzionare la tab stessa
            Text::make('Layers', function () {
                if ($this->layers->count() > 0) {
                    $out = '';
                    foreach ($this->layers as $l) {
                        $out .= '<a href="/resources/layers/' . $l->id . '">' . $l->name . '</a></br>';
                    }
                    return $out;
                } else {
                    return 'No Layers';
                }
            })->asHtml(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param Request $request
     *
     * @return array
     */
    public function cards(Request $request)
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
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function lenses(Request $request)
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
    public function actions(Request $request)
    {
        return [];
    }
}
