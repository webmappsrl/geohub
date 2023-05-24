<?php

namespace App\Nova;

use App\Enums\AppTiles;
use App\Helpers\NovaCurrentResourceActionHelper;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use App\Nova\Actions\elasticIndex;
use App\Nova\Actions\GenerateAppConfigAction;
use App\Nova\Actions\GenerateAppPoisAction;
use App\Rules\AppImagesRule;
use Davidpiesse\NovaToggle\Toggle;
use Eminiarts\Tabs\ActionsInTabs;
use Eminiarts\Tabs\Tab;
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
use Wm\MapMultiPurposeNova3\MapMultiPurposeNova3;

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

    private $languages  = [
        'en' => 'English',
        'it' => 'Italiano',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'es' => 'español'
    ];

    private $poi_interactions = [
        'no_interaction' => 'Nessuna interazione sul POI',
        'tooltip' => 'Apre un tooltip con informazioni minime',
        'popup' => ' Apre il popup',
        'tooltip_popup' => 'apre Tooltip con X per chiudere Tooltip oppure un bottone che apre il popup'
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
            Text::make(__('APP'), function () {
                $urlAny = 'https://' . $this->model()->id . '.app.webmapp.it';
                $urlDesktop = 'https://' . $this->model()->id . '.app.geohub.webmapp.it';
                $urlMobile = 'https://' . $this->model()->id . '.mobile.webmapp.it';
                return "
                <a class='btn btn-default btn-primary flex items-center justify-center px-3' style='margin:3px' href='$urlAny' target='_blank'>ANY</a>
                <a class='btn btn-default btn-primary flex items-center justify-center px-3' style='margin:3px' href='$urlDesktop' target='_blank'>DESKTOP</a>
                <a class='btn btn-default btn-primary flex items-center justify-center px-3' style='margin:3px' href='$urlMobile' target='_blank'>MOBILE</a>";
            })->asHtml(),
        ];
    }



    public function fieldsForDetail(Request $request)
    {
        if ($request->user()->can('Admin')) {
            return [
                (new Tabs("APP Details: {$this->name} ({$this->id})", $this->sections()))->withToolbar(),
            ];
        } else {

            $tab_array = [
                'APP' => $this->app_tab(),
                'HOME' => $this->home_tab(),
                'PROJECT' => $this->project_tab(),
                'ICONS' => $this->icons_tab(),
            ];
            if ($request->user()->hasDashboardShow($this->id)) {
                $tab_array = [
                    'APP' => $this->app_tab(),
                    'HOME' => $this->home_tab(),
                    'PROJECT' => $this->project_tab(),
                    'ICONS' => $this->icons_tab(),
                    // 'APP Analytics' => $this->app_analytics_tab(),
                    // 'POI Analytics' => $this->poi_analytics_tab(),
                    'MAP Analytics' => $this->map_analytics_tab(),
                ];
            }
            return [
                (new Tabs("APP Details: {$this->name} ({$this->id})", $tab_array))->withToolbar(),
            ];
        }
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
            Select::make(__('Default Language'), 'default_language')->hideFromIndex()->options($this->languages)->displayUsingLabels()->required(),
            Multiselect::make(__('Available Languages'), 'available_languages')->hideFromIndex()->options($this->languages, $availableLanguages)
        ];
    }
    public function fieldsForUpdate(Request $request)
    {
        if ($request->user()->can('Admin')) {
            return [
                (new Tabs("APP Details: {$this->name} ({$this->id})", $this->sections())),
            ];
        } else {
            return [
                (new Tabs("APP Details: {$this->name} ({$this->id})", [
                    'HOME' => $this->home_tab(),
                    'PROJECT' => $this->project_tab()
                ])),
            ];
        }
    }

    public function sections()
    {
        return [
            'APP' => $this->app_tab(),
            'WEBAPP' => $this->webapp_tab(),
            'HOME' => $this->home_tab(),
            'PROJECT' => $this->project_tab(),
            'AUTH' => $this->auth_tab(),
            'OFFLINE' => $this->offline_tab(),
            'ICONS' => $this->icons_tab(),
            'LANGUAGES' => $this->languages_tab(),
            'MAP' => $this->map_tab(),
            'FILTERS' => $this->filters_tab(),
            'OPTIONS' => $this->options_tab(),
            'POIS' => $this->pois_tab(),
            'ROUTING' => $this->routing_tab(),
            'TABLE' => $this->table_tab(),
            'THEME' => $this->theme_tab(),
            'LAYERS' => $this->layers_tab(),
            'OVERLAYS' => $this->overlayLayers_tab(),
        ];
    }

    protected function webapp_tab(): array
    {
        return [
            Toggle::make(__('Show draw track'), 'draw_track_show')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
            Toggle::make(__('Show editing inline'), 'editing_inline_show')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
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
            Text::make('API conf', function () {
                $url = route('api.app.webmapp.config', ['id' => $this->id]);
                return '<a class="btn btn-default btn-primary" href="' . $url . '" target="_blank">CONF</a>';
            })->asHtml(),
            Text::make(__('APP'), function () {
                $urlAny = 'https://' . $this->model()->id . '.app.webmapp.it';
                $urlDesktop = 'https://' . $this->model()->id . '.app.geohub.webmapp.it';
                $urlMobile = 'https://' . $this->model()->id . '.mobile.webmapp.it';
                return "
                <a class='btn btn-default btn-primary' style='margin:3px' href='$urlAny' target='_blank'>ANY</a>
                <a class='btn btn-default btn-primary' style='margin:3px' href='$urlDesktop' target='_blank'>DESKTOP</a>
                <a class='btn btn-default btn-primary' style='margin:3px' href='$urlMobile' target='_blank'>MOBILE</a>";
            })->asHtml()->onlyOnDetail(),
            Textarea::make('social_track_text')
                ->help(__('Add a description for meta tags of social share. You can customize the description with these keywords: {app.name} e {track.name}'))
                ->placeholder('Add social Meta Tag for description'),
            Boolean::make('dashboard_show')
        ];
    }

    protected function home_tab(): array
    {
        return [
            NovaTabTranslatable::make([
                NovaTinymce5Editor::make(__('welcome'), 'welcome')
                    ->help(__('is the welcome message displayed as the first element of the home')),
            ]),
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
            Select::make(__('Default Language'), 'default_language')->hideFromIndex()->options($this->languages)->displayUsingLabels(),
            Multiselect::make(__('Available Languages'), 'available_languages')->hideFromIndex()->options($this->languages, $availableLanguages)
        ];
    }

    protected function map_tab(): array
    {
        $selectedTileLayers = is_null($this->model()->tiles) ? [] : json_decode($this->model()->tiles, true);
        // $mapTilerApiKey = '0Z7ou7nfFFXipdDXHChf';
        $appTiles = new AppTiles();
        $t = $appTiles->oldval();
        return [
            NovaTabTranslatable::make([
                Text::make('Tiles Label')
            ]),
            Multiselect::make(__('Tiles'), 'tiles')->options($t, $selectedTileLayers)->help(__('Select which tile layers will be used by the app, the order is the same as the insertion order, so the last one inserted will be the one visible first')),
            // Multiselect::make(__('Tiles'), 'tiles')->options([
            //     "{\"notile\":\"\"}" => 'no tile',
            //     "{\"webmapp\":\"https://api.webmapp.it/tiles/{z}/{x}/{y}.png\"}" => 'webmapp',
            //     "{\"mute\":\"http://tiles.webmapp.it/blankmap/{z}/{x}/{y}.png\"}" => 'mute',
            //     "{\"satellite\":\"https://api.maptiler.com/tiles/satellite/{z}/{x}/{y}.jpg?key=$mapTilerApiKey\"}" => 'satellite',
            //     "{\"GOMBITELLI\":\"https://tiles.webmapp.it/mappa_gombitelli/{z}/{x}/{y}.png\"}" => 'GOMBITELLI',
            // ], $selectedTileLayers)->help(__('seleziona quali tile layer verranno utilizzati dalla app, l\' lordine è il medesimo di inserimento quindi l\'ultimo inserito sarà quello visibile per primo')),
            NovaSliderField::make(__('Max Zoom'), 'map_max_zoom')
                ->min(5)
                ->max(25)
                ->default(16)
                ->onlyOnForms(),
            Number::make(__('Max Stroke width'), 'map_max_stroke_width')
                ->min(0)
                ->max(19)
                ->default(6)
                ->help('Set max stoke width of line string, the max stroke width is applyed when the app is on max level zoom'),
            NovaSliderField::make(__('Min Zoom'), 'map_min_zoom')
                ->min(5)
                ->max(19)
                ->default(12),
            Number::make(__('Min Stroke width'), 'map_min_stroke_width')
                ->min(0)
                ->max(19)
                ->default(3)
                ->help('Set min stoke width of line string, the min stroke width is applyed when the app is on min level zoom'),
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
            Number::make(__('start_end_icons_min_zoom'))->min(10)->max(20)
                ->help('Set minimum zoom at which start and end icons are shown in general maps (start_end_icons_show must be true)'),
            Number::make(__('ref_on_track_min_zoom'))->min(10)->max(20)
                ->help('Set minimum zoom at which ref parameter is shown on tracks line in general maps (ref_on_track_show must be true)'),
            Text::make(__('POIS API'), function () {
                $url = '/api/v1/app/' . $this->model()->id . '/pois.geojson';
                return "<a class='btn btn-default btn-primary' href='$url' target='_blank'>POIS API</a>";
            })->asHtml()->onlyOnDetail(),
            Toggle::make('start_end_icons_show')
                ->help('Activate this option if you want to show start and end point of all tracks in the general maps. Use the start_end_icons_min_zoom option to set the minum zoom at which thi feature is activated.'),
            Toggle::make('ref_on_track_show')
                ->help('Activate this option if you want to show ref parameter on tracks line. Use the ref_on_track_min_zoom option to set the minum zoom at which thi feature is activated.'),
            Toggle::make(__('geolocation_record_enable'), 'geolocation_record_enable')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex()
                ->help('Activate this option if you want enable user track record'),
            Toggle::make('alert_poi_show')
                ->help('Activate this option if you want to show a poi proximity alert'),
            Number::make(__('alert_poi_radius'))->default(100)->help('set the radius(in meters) of the activation circle with center the user position, the nearest poi inside the circle trigger the alert'),
            Toggle::make('flow_line_quote_show')
                ->help('Activate this option if you want to color track by quote'),
            Number::make(__('flow_line_quote_orange'))->default(800)->help('defines the elevation by which the track turns orange'),
            Number::make(__('flow_line_quote_red'))->default(1500)->help('defines the elevation by which the track turns red'),
            //create a tab for filters fields

        ];
    }

    protected function filters_tab(): array
    {

        return [
            Boolean::make('Activity Filter', 'filter_activity')->help('Activate this option if you want to activate "Activity filter" for tracks'),
            Text::make('Activity Exclude Filter', 'filter_activity_exclude')->help('Insert the activities you want to exclude from the filter, separated by commas'),

            Boolean::make('Poi Type Filter', 'filter_poi_type')->help('Activate this option if you want to activate "Poi Type filter" for POIs'),
            Text::make('Poi Type Exclude Filter', 'filter_poi_type_exclude')->help('Insert the poi types you want to exclude from the filter, separated by commas'),
            
            Boolean::make('Track Duration Filter', 'filter_track_duration')->help('Activate this option if you want to filter tracks by duration. Make sure that "Show Pois layer on APP" option is turend on under POIS tab!'),
            Number::make('Track Min Duration Filter', 'filter_track_duration_min')->help('Set the minimum duration of the duration filter'),
            Number::make('Track Max Duration Filter', 'filter_track_duration_max')->help('Set the maximum duration of the duration filter'),
            Number::make('Track Duration Steps Filter', 'filter_track_duration_steps')->help('Set the steps of the duration filter'),
            
            Boolean::make('Track Distance Filter', 'filter_track_distance')->help('Activate this option if you want to filter tracks by distance'),
            Number::make('Track Min Distance Filter', 'filter_track_distance_min')->help('Set the minimum distance of the distance filter'),
            Number::make('Track Max Distance Filter', 'filter_track_distance_max')->help('Set the maximum distance of the distance filter'),
            Number::make('Track Distance Step Filter', 'filter_track_distance_steps')->help('Set the steps of the distance filter'),
            
            Boolean::make('Track Difficulty Filter', 'filter_track_difficulty')->help('Activate this option if you want to filter tracks by difficulty'),
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
            Toggle::make(__('download_track_enable'), 'download_track_enable')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex()
                ->help(__('Enable download of ever app track in GPX, KML, GEOJSON')),
            Toggle::make(__('print_track_enable'), 'print_track_enable')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex()
                ->help(__('Enable print of ever app track in PDF')),

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
            Select::make(__('Poi Interaction'), 'poi_interaction')->hideFromIndex()->options($this->poi_interactions)->displayUsingLabels()->required(),
            AttachMany::make('TaxonomyThemes'),
            Text::make('Themes', function () {
                if ($this->taxonomyThemes()->count() > 0) {
                    return implode(', ', $this->taxonomyThemes()->pluck('name')->toArray());
                }
                return 'No Themes';
            })->onlyOnDetail(),
            Text::make('Download GeoJSON collection', function () {
                $url = url('/api/v1/app/' . $this->id . '/pois.geojson');
                return '<a class="btn btn-default btn-primary" href="' . $url . '" target="_blank">Download</a>';
            })->asHtml()->onlyOnDetail(),
            Code::Make(__('POI acquisition forms'), 'poi_acquisition_form')
                ->language('json')
                ->rules('json')
                ->default(`
                [
                    {
                        "id" : "poi",
                        "label" : 
                        {
                            "it" : "Punto di interesse",
                            "en" : "Point of interest"
                        },
                        "fields" :
                        [
                            {
                                "name" : "title",
                                "type" : "text",
                                "placeholder": {
                                    "it" : "Inserisci un titolo",
                                    "en" : "Add a title"
                                },
                                "required" : true,
                                "label" : 
                                {
                                    "it" : "Titolo",
                                    "en" : "Title"
                                }
                            },
                            {
                                "name" : "waypointtype",
                                "type" : "select",
                                "required" : true,
                                "label" : 
                                {
                                    "it" : "Tipo punto di interesse",
                                    "en" : "Point of interest type"
                                },
                                "values" : [
                                    {
                                        "value" : "landscape",
                                        "label" :
                                        {
                                            "it" : "Panorama",
                                            "en" : "Landscape"
                                        }
                                    },
                                    {
                                        "value" : "place_to_eat",
                                        "label" :
                                        {
                                            "it" : "Luogo dove mangiare",
                                            "en" : "Place to eat"
                                        }
                                    },
                                    {
                                        "value" : "place_to_sleep",
                                        "label" :
                                        {
                                            "it" : "Luogo dove dormire",
                                            "en" : "Place to eat"
                                        }
                                    },
                                    {
                                        "value" : "natural",
                                        "label" :
                                        {
                                            "it" : "Luogo di interesse naturalistico",
                                            "en" : "Place of naturalistic interest"
                                        }
                                    },
                                    {
                                        "value" : "cultural",
                                        "label" :
                                        {
                                            "it" : "Luogo di interesse culturale",
                                            "en" : "Place of cultural interest"
                                        }
                                    },
                                    {
                                        "value" : "other",
                                        "label" :
                                        {
                                            "it" : "Altri tipi ti luoghi di interesse",
                                            "en" : "Other types of Point of interest"
                                        }
                                    }
                                ]
                            },
                            {
                                "name" : "description",
                                "type" : "textarea",
                                "placeholder": {
                                    "it" : "Se vuoi puoi aggiungere una descrizione",
                                    "en" : "You can add a description if you want"
                                },
                                "required" : false,
                                "label" : 
                                {
                                    "it" : "Descrizione",
                                    "en" : "Title"
                                }
                            }
                        ] 
                    }
                ]`)
                ->help(
                    view('poi-forms')->render()
                )
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
            Code::Make(__('iconmoon selection.json'), 'iconmoon_selection')->language('json')->rules('nullable', 'json')->help(
                'import icoonmoon selection.json file'
            )
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

    protected function overlayLayers_tab(): array
    {
        return [
            NovaTabTranslatable::make([
                Text::make('Overlays Label', 'overlays_label')
            ]),
            Text::make('Overlay Layer', function () {
                if ($this->overlayLayers->count() > 0) {
                    $out = '';
                    foreach ($this->overlayLayers as $l) {
                        $out .= '<a href="/resources/overlay-layers/' . $l->id . '">' . $l->name . '</a></br>';
                    }
                    return $out;
                } else {
                    return 'No Overlay Layers';
                }
            })->asHtml(),
        ];
    }

    protected function app_analytics_tab(): array
    {
        return [
            Text::make('Impressions', function () {
                $html = '<table style="width: 100%;" border="1" cellpadding="10"><tbody><tr><td style="width: 50%;"><strong>iOS</strong></td><td style="width: 50%;"><strong>Android</strong></td></tr><tr><td>5.8K</td><td></td></tr></tbody></table><p style="font-size:14px;"><i>The number of times the App\'s icon was viewed on the store.</i></p>';
                return $html;
            })->asHtml(),
            Text::make('Product Page View', function () {
                $html = '<table style="width: 100%;" border="1" cellpadding="10"><tbody><tr><td style="width: 50%;"><strong>iOS</strong></td><td style="width: 50%;"><strong style="width: 50%;">Android</strong></td></tr><tr><td>696</td><td></td></tr></tbody></table><p style="font-size:14px;"><i>The number of times the App\'s product page was viewed on the store.</i></p>';
                return $html;
            })->asHtml(),
            Text::make('Conversion rate', function () {
                $html = '<table style="width: 100%;" border="1" cellpadding="10"><tbody><tr><td style="width: 50%;"><strong>iOS</strong></td><td style="width: 50%;"><strong>Android</strong></td></tr><tr><td>11.4%</td><td></td></tr></tbody></table><p style="font-size:14px;"><i>Calculated by dividing total downloads by unique device impressions.</i></p>';
                return $html;
            })->asHtml(),
            Text::make('Total Download', function () {
                $html = '<table style="width: 100%;" border="1" cellpadding="10"><tbody><tr><td style="width: 50%;"><strong>iOS</strong></td><td style="width: 50%;"><strong>Android</strong></td></tr><tr><td>506</td><td>303</td></tr></tbody></table><p style="font-size:14px;"><i>The number of first-time downloads and redownloads.</i></p>';
                return $html;
            })->asHtml(),
        ];
    }

    protected function poi_analytics_tab(): array
    {
        $mostviewedpois = $this->model()->getMostViewedPoiGeojson();
        return [
            MapMultiPurposeNova3::make('Most Viewed POIs Map')->withMeta([
                'center' => ["43", "10"],
                'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                'defaultZoom' => 10,
                'poigeojson' => $mostviewedpois
            ]),
            Text::make('Most Viewed POIs List', function () use ($mostviewedpois) {
                $html = '<table style="width: 100%;" border="1" cellpadding="10"><tbody>';
                $collection = json_decode($mostviewedpois);
                foreach ($collection->features as $count => $feature) {
                    $count++;
                    $html .= '<tr><td style="width: 50%;">' . $count . ' - ' . $feature->properties->name . '</td><td style="width: 50%;"><strong>' . $feature->properties->visits . '</strong> visits</td></tr>';
                }
                $html .= '</tbody></table>';
                return $html;
            })->asHtml(),
        ];
    }

    protected function map_analytics_tab(): array
    {
        $poigeojson = $this->model()->getUGCPoiGeojson($this->model()->app_id);
        $mediageojson = $this->model()->getUGCMediaGeojson($this->model()->app_id);
        $trackgeojson = $this->model()->getiUGCTrackGeojson($this->model()->app_id);
        return [
            MapMultiPurposeNova3::make('All user created contents')->withMeta([
                'center' => ["43", "12"],
                'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                'defaultZoom' => 5,
                'poigeojson' => $poigeojson,
                'mediageojson' => $mediageojson,
                'trackgeojson' => $trackgeojson
            ]),
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
        return [
            (new elasticIndex())->canSee(function ($request) {
                return true;
            })->canRun(function ($request, $zone) {
                return true;
            }),
            (new GenerateAppConfigAction())->canSee(function ($request) {
                return true;
            })->canRun(function ($request, $zone) {
                return true;
            }),
            (new GenerateAppPoisAction())->canSee(function ($request) {
                return true;
            })->canRun(function ($request, $zone) {
                return true;
            })
        ];
    }
}
