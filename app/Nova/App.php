<?php

namespace App\Nova;

use App\Enums\AppTiles;
use App\Helpers\NovaCurrentResourceActionHelper;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use App\Nova\Actions\elasticIndex;
use App\Nova\Actions\GenerateAppConfigAction;
use App\Nova\Actions\GenerateAppPoisAction;
use App\Nova\Actions\generateQrCodeAction;
use App\Nova\Actions\GenerateUgcMediaRankingAction;
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
use Laravel\Nova\Fields\DateTime;
use OptimistDigital\MultiselectField\Multiselect as MultiselectFieldMultiselect;
use Titasgailius\SearchRelations\SearchesRelations;
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
    use SearchesRelations;

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
        'name', 'customer_name', 'id'
    ];

    private $languages  = [
        'en' => 'English',
        'it' => 'Italiano',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'es' => 'español'
    ];

    /**
     * The relationship columns that should be searched.
     *
     * @var array
     */
    public static $searchRelations = [
        'author' => ['name', 'email'],
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
                'RELEASE DATA' => $this->app_release_data_tab(),
                'HOME' => $this->home_tab(),
                'PAGES' => $this->pages_tab(),
                'ICONS' => $this->icons_tab(),
            ];
            if ($request->user()->hasDashboardShow($this->id)) {
                $tab_array = [
                    'APP' => $this->app_tab(),
                    'HOME' => $this->home_tab(),
                    'PAGES' => $this->pages_tab(),
                    'ICONS' => $this->icons_tab(),
                    // 'APP Analytics' => $this->app_analytics_tab(),
                    // 'POI Analytics' => $this->poi_analytics_tab(),
                    'MAP Analytics' => $this->map_analytics_tab(),
                ];
            }
            if ($request->user()->hasClassificationShow($this->id)) {
                $tab_array['Classification'] = $this->ugc_media_classification_tab();
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
                    'PAGES' => $this->pages_tab()
                ])),
            ];
        }
    }

    public function sections()
    {
        return [
            'APP' => $this->app_tab(),
            'TRANSLATIONS' => $this->translations_tab(),
            'RELEASE DATA' => $this->app_release_data_tab(),
            'WEBAPP' => $this->webapp_tab(),
            'HOME' => $this->home_tab(),
            'PAGES' => $this->pages_tab(),
            'AUTH' => $this->auth_tab(),
            'OFFLINE' => $this->offline_tab(),
            'ICONS' => $this->icons_tab(),
            'LANGUAGES' => $this->languages_tab(),
            'MAP' => $this->map_tab(),
            'FILTERS' => $this->filters_tab(),
            'SEARCHABLE' => $this->searchable_tab(),
            'OPTIONS' => $this->options_tab(),
            'POIS' => $this->pois_tab(),
            'ROUTING' => $this->routing_tab(),
            'TABLE' => $this->table_tab(),
            'THEME' => $this->theme_tab(),
            'LAYERS' => $this->layers_tab(),
            'OVERLAYS' => $this->overlayLayers_tab(),
            'ACQUISITION FORM' => $this->acquisition_form_tab()
        ];
    }

    protected function webapp_tab(): array
    {
        return [
            Toggle::make(__('Show draw track'), 'draw_track_show')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex()
                ->help(__('Enables the draw a track')),
            Toggle::make(__('Show editing inline'), 'editing_inline_show')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex(),
            Toggle::make(__('Show splash screen'), 'splash_screen_show')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex()
                ->help(__('Show splash screen on startup')),
            Text::make(__('Google universal ID'), 'gu_id'),
            Code::make(__('Embed Code'), 'embed_code_body')
                ->help(__('Embed scripts for body section. Include script tag to your code.'))
        ];
    }

    protected function app_tab(): array
    {
        return [
            Text::make(__('Name'), 'customer_name')->sortable()->required(),
            Select::make(__('API type'), 'api')->options(
                [
                    'elbrus' => 'Elbrus',
                    'webmapp' => 'WebMapp',
                    'webapp' => 'WebApp',
                ]
            )->required(),
            Text::make(__('App Id'), 'app_id')
                ->required()
                ->help(__('The package ID must match the one used in stores. It cannot be changed after the first build is uploaded.')),
            Text::make(__('Play Store link (android)'), 'android_store_link'),
            Text::make(__('App Store link (iOS)'), 'ios_store_link'),
            BelongsTo::make('Author', 'author', User::class)
                ->searchable()
                ->nullable()
                ->canSee(function ($request) {
                    return $request->user()->can('Admin', $this);
                })
                ->help(__('User author associated')),
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
            Textarea::make(__('Social track text'), 'social_track_text')
                ->help(__('Add a description for meta tags of social share. You can customize the description with these keywords: {app.name} e {track.name}'))
                ->placeholder('Add social Meta Tag for description'),
            NovaTabTranslatable::make([
                Text::make('Social share text', 'social_share_text')
                    ->help(__('This is shown when a Track is being shared via mobile apps.')),
            ]),
            Boolean::make(__('Activate dashboard'), 'dashboard_show')
                ->help(__('Activate the dashboard for users, you also need to activate authentication in the AUTH tab')),
            Boolean::make(__('Activate classificationon Ugc Media'), 'classification_show')
                ->help(__('Enable user-generated ranking via UGC media')),
            DateTime::make('Classification Start Date', 'classification_start_date')
                ->rules('required_if:classification_show,true')
                ->hideFromIndex(),
            DateTime::make('Classification End Date', 'classification_end_date')
                ->rules('required_if:classification_show,true')
                ->hideFromIndex(),
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
            ),
            Toggle::make(__('Show searchbar'), 'show_search')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(true)
                ->hideFromIndex()
                ->help(__('Activate to show the search bar on the home')),
        ];
    }

    protected function pages_tab(): array
    {
        return [
            NovaTabTranslatable::make([
                NovaTinymce5Editor::make('Page Project', 'page_project'),
                NovaTinymce5Editor::make('Page Disclaimer', 'page_disclaimer'),
                NovaTinymce5Editor::make('Page Credits', 'page_credits'),
                NovaTinymce5Editor::make('Page Privacy', 'page_privacy'),
            ])
        ];
    }
    protected function languages_tab(): array
    {

        $availableLanguages = is_null($this->model()->available_languages) ? [] : json_decode($this->model()->available_languages, true);

        return [
            Select::make(__('Default Language'), 'default_language')->hideFromIndex()->options($this->languages)->displayUsingLabels(),
            Multiselect::make(__('Available Languages'), 'available_languages')
                ->hideFromIndex()
                ->options($this->languages, $availableLanguages)
                ->help(__('Select languages ​​for app translations'))
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
            // use OptimistDigital\MultiselectField\Multiselect;
            MultiselectFieldMultiselect::make(__('Tiles'), 'tiles')->options($t, $selectedTileLayers)->help(__('Select which tile layers will be used by the app, the order is the same as the insertion order, so the last one inserted will be the one visible first'))->reorderable(),
            // Multiselect::make(__('Tiles'), 'tiles')->options($t, $selectedTileLayers)->help(__('Select which tile layers will be used by the app, the order is the same as the insertion order, so the last one inserted will be the one visible first')),
            // Multiselect::make(__('Tiles'), 'tiles')->options([
            //     "{\"notile\":\"\"}" => 'no tile',
            //     "{\"webmapp\":\"https://api.webmapp.it/tiles/{z}/{x}/{y}.png\"}" => 'webmapp',
            //     "{\"mute\":\"http://tiles.webmapp.it/blankmap/{z}/{x}/{y}.png\"}" => 'mute',
            //     "{\"satellite\":\"https://api.maptiler.com/tiles/satellite/{z}/{x}/{y}.jpg?key=$mapTilerApiKey\"}" => 'satellite',
            //     "{\"GOMBITELLI\":\"https://tiles.webmapp.it/mappa_gombitelli/{z}/{x}/{y}.png\"}" => 'GOMBITELLI',
            // ], $selectedTileLayers)->help(__('seleziona quali tile layer verranno utilizzati dalla app, l\' lordine è il medesimo di inserimento quindi l\'ultimo inserito sarà quello visibile per primo')),
            NovaTabTranslatable::make([
                Text::make('Data Label'),
                Text::make('Pois Data Label'),
                Text::make('Tracks Data Label')
            ]),
            Boolean::make('Show POIs data by default', 'pois_data_default')
                ->help(__('Turn this option off if you do not want to show pois by default on the map')),
            Text::make('poi Data Icon', 'pois_data_icon', function () {
                return "<div style='width:64px;height:64px;'>" . $this->pois_data_icon . "</div>";
            })->asHtml()->onlyOnDetail(),
            Textarea::make('Poi Data Icon SVG', 'pois_data_icon')
                ->onlyOnForms()
                ->hideWhenCreating()
                ->help(__('svg icon shown in the filter')),
            Boolean::make('Show Tracks data by default', 'tracks_data_default')
                ->help(__('Turn this option off if you do not want to show all track layers by default on the map')),
            Text::make('Track Data Icon', 'tracks_data_icon', function () {
                return "<div style='width:64px;height:64px;'>" . $this->tracks_data_icon . "</div>";
            })->asHtml()->onlyOnDetail(),
            Textarea::make('Track Data Icon SVG', 'tracks_data_icon')
                ->onlyOnForms()
                ->hideWhenCreating()
                ->help(__('svg icon shown in the filter')),
            NovaSliderField::make(__('Max Zoom'), 'map_max_zoom')
                ->min(5)
                ->max(25)
                ->default(16)
                ->onlyOnForms(),
            Number::make(__('Max Stroke width'), 'map_max_stroke_width')
                ->min(0)
                ->max(19)
                ->default(6)
                ->help(__('Set max stoke width of line string, the max stroke width is applyed when the app is on max level zoom')),
            NovaSliderField::make(__('Min Zoom'), 'map_min_zoom')
                ->min(5)
                ->max(19)
                ->default(12),
            Number::make(__('Min Stroke width'), 'map_min_stroke_width')
                ->min(0)
                ->max(19)
                ->default(3)
                ->help(__('Set min stoke width of line string, the min stroke width is applyed when the app is on min level zoom')),
            NovaSliderField::make(__('Def Zoom'), 'map_def_zoom')
                ->min(5)
                ->max(19)
                ->interval(0.1)
                ->default(12)
                ->onlyOnForms()
                ->help(__('App default zoom')),
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
                ])
                ->help(__('Bounding the map view <a href="https://boundingbox.klokantech.com/" target="_blank">create a bounding box</a>')),

            Number::make(__('Max Zoom'), 'map_max_zoom')->onlyOnDetail(),
            Number::make(__('Min Zoom'), 'map_min_zoom')->onlyOnDetail(),
            Number::make(__('Def Zoom'), 'map_def_zoom')->onlyOnDetail(),
            Text::make(__('Bounding BOX'), 'bbox')->onlyOnDetail(),
            Number::make(__('start_end_icons_min_zoom'))->min(10)->max(20)
                ->help(__('Set minimum zoom at which start and end icons are shown in general maps (start_end_icons_show must be true)')),
            Number::make(__('ref_on_track_min_zoom'))->min(10)->max(20)
                ->help(__('Set minimum zoom at which ref parameter is shown on tracks line in general maps (ref_on_track_show must be true)')),
            Text::make(__('POIS API'), function () {
                $url = '/api/v1/app/' . $this->model()->id . '/pois.geojson';
                return "<a class='btn btn-default btn-primary' href='$url' target='_blank'>POIS API</a>";
            })->asHtml()->onlyOnDetail(),
            Toggle::make('start_end_icons_show')
                ->help(__('Activate this option if you want to show start and end point of all tracks in the general maps. Use the start_end_icons_min_zoom option to set the minum zoom at which thi feature is activated.')),
            Toggle::make('ref_on_track_show')
                ->help(__('Activate this option if you want to show ref parameter on tracks line. Use the ref_on_track_min_zoom option to set the minum zoom at which thi feature is activated.')),
            Toggle::make(__('geolocation_record_enable'), 'geolocation_record_enable')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex()
                ->help(__('Activate this option if you want enable user track record')),
            Select::make(__('GPS Accuracy Default'), 'gps_accuracy_default')
                ->options([
                    '5' => '5 meters',
                    '10' => '10 meters', // default
                    '20' => '20 meters',
                    '100' => '100 meters'
                ])
                ->displayUsingLabels(),
            Toggle::make('alert_poi_show')
                ->help(__('Activate this option if you want to show a poi proximity alert')),
            Number::make(__('alert_poi_radius'))
                ->default(100)
                ->help(__('set the radius(in meters) of the activation circle with center the user position, the nearest poi inside the circle trigger the alert')),
            Toggle::make('flow_line_quote_show')
                ->help(__('Activate this option if you want to color track by quote')),
            Number::make(__('flow_line_quote_orange'))
                ->default(800)
                ->help(__('defines the elevation by which the track turns orange')),
            Number::make(__('flow_line_quote_red'))
                ->default(1500)
                ->help(__('defines the elevation by which the track turns red')),
            //create a tab for filters fields

        ];
    }

    protected function filters_tab(): array
    {

        return [
            NovaTabTranslatable::make([
                Text::make('Activity Filter Label', 'filter_activity_label'),
                Text::make('Theme Filter Label', 'filter_theme_label'),
                Text::make('Poi Type Filter Label', 'filter_poi_type_label'),
                Text::make('Duration Filter Label', 'filter_track_duration_label'),
                Text::make('Distance Filter Label', 'filter_track_distance_label')
            ]),
            Boolean::make('Activity Filter', 'filter_activity')->help(__('Activate this option if you want to activate "Activity filter" for tracks')),
            Text::make('Activity Exclude Filter', 'filter_activity_exclude')->help(__('Insert the activities you want to exclude from the filter, separated by commas')),

            Boolean::make('Theme Filter', 'filter_theme')->help(__('Activate this option if you want to activate "Theme filter" for tracks')),
            Text::make('Theme Exclude Filter', 'filter_theme_exclude')->help(__('Insert the themes you want to exclude from the filter, separated by commas')),

            Boolean::make('Poi Type Filter', 'filter_poi_type')->help(__('Activate this option if you want to activate "Poi Type filter" for POIs')),
            Text::make('Poi Type Exclude Filter', 'filter_poi_type_exclude')->help(__('Insert the poi types you want to exclude from the filter, separated by commas')),

            Boolean::make('Track Duration Filter', 'filter_track_duration')->help(__('Activate this option if you want to filter tracks by duration. Make sure that "Show Pois layer on APP" option is turend on under POIS tab!')),
            Number::make('Track Min Duration Filter', 'filter_track_duration_min')->help(__('Set the minimum duration of the duration filter')),
            Number::make('Track Max Duration Filter', 'filter_track_duration_max')->help(__('Set the maximum duration of the duration filter')),
            Number::make('Track Duration Steps Filter', 'filter_track_duration_steps')->help(__('Set the steps of the duration filter')),

            Boolean::make('Track Distance Filter', 'filter_track_distance')->help(__('Activate this option if you want to filter tracks by distance')),
            Number::make('Track Min Distance Filter', 'filter_track_distance_min')->help(__('Set the minimum distance of the distance filter')),
            Number::make('Track Max Distance Filter', 'filter_track_distance_max')->help(__('Set the maximum distance of the distance filter')),
            Number::make('Track Distance Step Filter', 'filter_track_distance_steps')->help(__('Set the steps of the distance filter')),

            Boolean::make('Track Difficulty Filter', 'filter_track_difficulty')->help(__('Activate this option if you want to filter tracks by difficulty')),
        ];
    }

    protected function searchable_tab(): array
    {
        $track_selected = is_null($this->model()->track_searchables) ? [] : json_decode($this->model()->track_searchables, true);
        $poi_selected = is_null($this->model()->poi_searchables) ? [] : json_decode($this->model()->poi_searchables, true);
        return [
            MultiSelect::make(__('Track Search In'), 'track_searchables')->options([
                'name' => 'Name',
                'description' => 'Description',
                'excerpt' => 'Excerpt',
                'ref' => 'REF',
                'osmid' => 'OSMID',
                'taxonomyThemes' => 'Themes',
                'taxonomyActivities' => 'Activity',
            ], $track_selected),
            MultiSelect::make(__('POI Search In'), 'poi_searchables')->options([
                'name' => 'Name',
                'description' => 'Description',
                'excerpt' => 'Excerpt',
                'osmid' => 'OSMID',
                'taxonomyPoiTypes' => 'POI Type',
            ], $poi_selected),
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
                ->colors('text-advanced')->withProps([
                    'show-fallback' => true,
                    'fallback-type' => 'input',
                ])
                ->hideFromIndex(),
            Swatches::make(__('Primary color'), 'primary_color')
                ->default('#de1b0d')
                ->colors('text-advanced')->withProps([
                    'show-fallback' => true,
                    'fallback-type' => 'input',
                ])->hideFromIndex(),
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
    protected function translations_tab(): array
    {
        return [
            Code::make('Italian Translations', 'translations_it')
                ->language('json')
                ->rules('nullable', 'json')
                ->help(_('Enter the Italian translations in JSON format here')),

            Code::make('English Translations', 'translations_en')
                ->language('json')
                ->rules('nullable', 'json')
                ->help(__('Enter the Italian translations in JSON format here')),

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
            Select::make(__('Poi Interaction'), 'poi_interaction')
                ->hideFromIndex()
                ->options($this->poi_interactions)
                ->displayUsingLabels()
                ->required()
                ->help(__('Click interaction on a poi')),
            AttachMany::make('TaxonomyThemes'),
            Text::make('Themes', function () {
                if ($this->taxonomyThemes()->count() > 0) {
                    return implode(', ', $this->taxonomyThemes()->pluck('name')->toArray());
                }
                return 'No Themes';
            })
                ->onlyOnDetail()
                ->help(__('Select the theme taxonomy to view all poi')),
            Text::make('Download GeoJSON collection', function () {
                $url = url('/api/v1/app/' . $this->id . '/pois.geojson');
                return '<a class="btn btn-default btn-primary" href="' . $url . '" target="_blank">Download</a>';
            })->asHtml()->onlyOnDetail(),
        ];
    }

    protected function auth_tab(): array
    {
        return [
            Toggle::make(__('Show Auth at startup'), 'auth_show_at_startup')
                ->trueValue('On')
                ->falseValue('Off')
                ->default(false)
                ->hideFromIndex()
                ->help(__('shows the authentication and registration page for users')),
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
                ->hideFromIndex()
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
            Image::make(__('Logo Homepage'), 'logo_homepage')
                ->rules('image', 'mimes:svg')
                ->disk('public')
                ->path('api/app/' . $this->model()->id . '/resources')
                ->storeAs(function () {
                    return 'logo_homepage.svg';
                })
                ->help(__('Required svg image'))
                ->hideFromIndex(),
            Text::make('QR Code custom URL', 'qrcode_custom_url')->help('Leave this field empty for default webapp URL'),
            Text::make('QR Code', 'qr_code', function () {
                return "<div style='width:64px;height:64px; display:flex; align-items:center;'>" . $this->qr_code . "</div>";
            })->asHtml(),
            Code::Make(__('iconmoon selection.json'), 'iconmoon_selection')
                ->language('json')
                ->rules('nullable', 'json')
                ->help(__('Follow this guide: <a href="https://docs.google.com/document/d/1CDYRMTq9Unn545Ug7kYX0Ot1IZ0VLpe5/edit?usp=sharing&ouid=112992720252972804016&rtpof=true&sd=true" target="_blank">Google Docs Guide</a> then upload the Selection.json file'))
        ];
    }

    protected function app_release_data_tab(): array
    {
        return [
            Text::make(__('Name'), 'name')->sortable()->required(),

            Textarea::make(__('Short Description'), 'short_description')
                ->hideFromIndex()
                ->rules('max:80')
                ->help(__('Max 80 characters. To be used as a promotional message also.')),

            NovaTinymce5Editor::make(__('Long Description'), 'long_description')
                ->hideFromIndex()
                ->rules('max:4000')
                ->help(__('Max 4000 characters.')),

            Text::make(__('Keywords'), 'keywords')
                ->hideFromIndex()
                ->help(__('Comma separated Keywords e.g. "hiking,trekking,map"')),

            Text::make(__('Privacy Url'), 'privacy_url')
                ->hideFromIndex()
                ->help(__('Url to the privacy policy')),

            Text::make(__('Website Url'), 'website_url')
                ->hideFromIndex()
                ->help(__('Url to the website')),
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

            // Image::make(__('Feature image'), 'feature_image')
            //     ->rules('image', 'mimes:png', 'dimensions:width=1024,height=500')
            //     ->disk('public')
            //     ->path('api/app/' . $this->model()->id . '/resources')
            //     ->storeAs(function () {
            //         return 'feature_image.png';
            //     })
            //     ->help(__('Required size is :widthx:heightpx', ['width' => 1024, 'height' => 500]))
            //     ->hideFromIndex(),

            // Image::make(__('Icon Notify'), 'icon_notify')
            //     ->rules('image', 'mimes:png', 'dimensions:ratio=1')
            //     ->disk('public')
            //     ->path('api/app/' . $this->model()->id . '/resources')
            //     ->storeAs(function () {
            //         return 'icon_notify.png';
            //     })
            //     ->help(__('Required square png. Transparency is allowed and recommended for the background'))
            //     ->hideFromIndex(),
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
            Boolean::make('Generate All Layers Edges', 'generate_layers_edges')
                ->help('Enable the Edge feature on all layers of the app'),
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
            })->asHtml()

        ];
    }

    protected function overlayLayers_tab(): array
    {
        return [
            NovaTabTranslatable::make([
                Text::make('Overlays Label', 'overlays_label')
                    ->help(__('Label displayed in the overlay filter')),
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
            })->asHtml()
        ];
    }

    protected function acquisition_form_tab(): array
    {
        return [
            Code::Make(__('POI acquisition forms'), 'poi_acquisition_form')
                ->language('json')
                ->rules('json')
                ->default(`
            [
                {
                    "id" : "poi",
                    "helper": {
                        "ït": "sono helper di Punto di interesse",
                        "en": "helper of Point of interest"
                      },
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
                ),
            Code::Make(__('TRACK acquisition forms'), 'track_acquisition_form')
                ->language('json')
                ->rules('json')
                ->default(`
            [
                {
                    "id" : "track",
                    "helper": {
                        "ït": "sono helper di track",
                        "en": "helper of track"
                      },
                    "label" : 
                    {
                        "it" : "traccia",
                        "en" : "track"
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
                            "name" : "tracktype",
                            "type" : "select",
                            "required" : true,
                            "label" : 
                            {
                                "it" : "Tipo traccia",
                                "en" : "Track type"
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
                    view('track-forms')->render()
                )
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
    protected function ugc_media_classification_tab(): array
    {
        $html = 'No results yet! Run the classification action to see the results.';
        $classification = $this->getRankedUsersNearPois();
        if (!empty($classification)) {
            // Decode the JSON into un associative array

            // Sort users by the number of POIs


            // Start the HTML table
            $html = <<<HTML
                <table border="1" style="border-collapse: collapse; width: 100%;">
                    <tr>
                        <th style="padding: 8px; text-align: left;">User ID (Email)</th>
                        <th style="padding: 8px; text-align: left;">Score</th>
                    </tr>
            HTML;
            $rank = 0;
            foreach ($classification as $userId => $pois) {
                $rank++;
                // Assuming getUserEmailById() is a function that retrieves the user's email by their ID
                $userEmail = $this->getUserEmailById($userId); // You'll need to define this function
                $count = count($pois);

                // Row for user info
                $html .= <<<HTML
                    <tr style="background-color:yellow">
                        <td style="font-weight: bold; padding: 8px; text-align: left">{$rank}) {$userId} ({$userEmail})</td>
                        <td style="padding: 8px; text-align: left">{$count}</td>
                    </tr>
                HTML;

                // Rows for POI details
                foreach ($pois as $index => $poi) {
                    $name = $poi['ec_poi']['name']; // Assuming 'name' is the field for POI name
                    $mediaIds = explode(',', $poi['media_ids']);
                    $position = $index + 1;

                    $html .= <<<HTML
                    <tr>
                        <td style="padding: 2px;">
                            <div style="display: flex; flex-wrap: wrap; align-items: center;">
                                <div style="margin-bottom: 8px; width: 100%;">{$position}) {$name}</div>
                    HTML;

                    foreach ($mediaIds as $mediaId) {
                        $imageUrl = env('APP_URL') . '/storage/media/images/ugc/image_' . $mediaId . '.jpg'; // Assuming 'media_ids' is the ID for the image
                        $UgcMediaUrl = env('APP_URL') . '/resources/ugc-medias/' . $mediaId;
                        $html .= <<<HTML
                                <div style="flex: 0 0 5%; text-align: center;">
                                    <a href="{$UgcMediaUrl}" target="_blank">
                                        <img src="{$imageUrl}" style="max-width: 32px; max-height: 32px; display: block; margin: 0 auto;" />
                                    </a>
                                </div>
                        HTML;
                    }

                    $html .= <<<HTML
                            </div>
                        </td>
                    </tr>
                    HTML;
                }
            }

            $html .= '</table>';
        }
        return [
            Text::make(__('Top ten'), 'classification', function () use ($html) {
                return $html;
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
            }),
            (new generateQrCodeAction())->canSee(function ($request) {
                return true;
            })->canRun(function ($request, $zone) {
                return true;
            }),

        ];
    }
}
