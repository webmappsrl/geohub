<?php

namespace App\Nova;

use Exception;
use Laravel\Nova\Panel;
use Eminiarts\Tabs\Tabs;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Text;
use Eminiarts\Tabs\TabsOnEdit;
use NovaAttachMany\AttachMany;
use Yna\NovaSwatches\Swatches;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Heading;
use App\Nova\Actions\ExportEcpoi;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\KeyValue;
use Laravel\Nova\Fields\Textarea;
use Davidpiesse\NovaToggle\Toggle;
use Laravel\Nova\Fields\BelongsTo;
use Webmapp\Ecpoipopup\Ecpoipopup;
use Wm\MapPointNova3\MapPointNova3;
use App\Nova\Filters\HasDescription;
use App\Nova\Actions\RegenerateEcPoi;
use App\Nova\Filters\HasFeatureImage;
use App\Nova\Filters\HasImageGallery;
use App\Nova\Metrics\EcTracksMyValue;
use App\Nova\Metrics\EcTracksNewValue;
use DigitalCreative\MegaFilter\Column;
use Laravel\Nova\Fields\BelongsToMany;
use Webmapp\EcMediaPopup\EcMediaPopup;
use Illuminate\Support\Facades\Storage;
use App\Nova\Metrics\EcTracksTotalValue;
use App\Nova\Filters\SelectFromThemesPoi;
use App\Nova\Filters\SelectFromWheresPoi;
use DigitalCreative\MegaFilter\MegaFilter;
use App\Nova\Filters\SelectFromPoiTypesPoi;
use Chaseconey\ExternalImage\ExternalImage;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Nova\Filters\EcTracksCaiScaleFilter;
use App\Nova\Filters\PoiSearchableFromOSMID;
use App\Nova\Actions\DownloadExcelEcPoiAction;
use App\Helpers\NovaCurrentResourceActionHelper;
use Webmapp\FeatureImagePopup\FeatureImagePopup;
use PosLifestyle\DateRangeFilter\DateRangeFilter;
use DigitalCreative\MegaFilter\HasMegaFilterTrait;
use Kraftbit\NovaTinymce5Editor\NovaTinymce5Editor;
use Titasgailius\SearchRelations\SearchesRelations;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Maatwebsite\LaravelNovaExcel\Actions\DownloadExcel;

class EcPoi extends Resource
{


    use TabsOnEdit, SearchesRelations;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\EcPoi::class;
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

    /**
     * The relationship columns that should be searched.
     *
     * @var array
     */
    public static $searchRelations = [
        'author' => ['name', 'email'],
        'taxonomyActivities' => ['name'],
        'taxonomyWheres' => ['name'],
        'taxonomyTargets' => ['name'],
        'taxonomyWhens' => ['name'],
        'taxonomyThemes' => ['name'],
    ];

    public static function group()
    {
        return __('Editorial Content');
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
        return $query->where('user_id', $request->user()->id);
    }


    /**
     * Get the fields displayed by the resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function fields(Request $request)
    {

        return [
            ID::make('id'),
            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name'),
                // Text::make(__('Audio'),'audio'),
            ]),
            AttachMany::make('TaxonomyPoiTypes'),
            AttachMany::make('TaxonomyActivities'),
            AttachMany::make('TaxonomyTargets'),
            AttachMany::make('TaxonomyThemes'),
            // Do not remove below code, necessary for data binding
            BelongsToMany::make('Gallery', 'ecMedia', 'App\Nova\EcMedia')->searchable()->nullable(),
        ];
    }


    public function fieldsForIndex(Request $request)
    {

        if ($request->user()->can('Admin')) {
            return [
                Text::make('Name')->sortable(),

                BelongsTo::make('Author', 'author', User::class)->sortable(),

                DateTime::make(__('Created At'), 'created_at')->sortable(),

                DateTime::make(__('Updated At'), 'updated_at')->sortable(),

                // Text::make('API',function () {
                //     return '<a href="'.route('api.ec.poi.json', ['id' => $this->id]).'" target="_blank">[x]</a>';
                // })->asHtml(),
                Text::make('API', function () {
                    return '<a href="/api/ec/poi/' . $this->id . '" target="_blank">[x]</a>';
                })->asHtml(),
            ];
        } else {
            return [
                Text::make('Name')->sortable(),
                Boolean::make('Description', function () {
                    if ($this->description) {
                        return true;
                    } else {
                        return false;
                    }
                }),
                Boolean::make('Feature Image', function () {
                    if ($this->featureImage) {
                        return true;
                    } else {
                        return false;
                    }
                }),
                Boolean::make('Image Gallery', function () {
                    if (count($this->ecMedia) > 0) {
                        return true;
                    } else {
                        return false;
                    }
                }),
                Text::make('Poi Types', function () {
                    if ($this->taxonomyPoiTypes()->count() > 0) {
                        return implode('<br/>', $this->taxonomyPoiTypes()->pluck('name')->toArray());
                    }
                    return 'No Poi Types';
                })->asHtml(),

                Text::make('Themes', function () {
                    if ($this->taxonomyThemes()->count() > 0) {
                        return implode('<br/>', $this->taxonomyThemes()->pluck('name')->toArray());
                    }
                    return 'No Themes';
                })->asHtml(),

                Text::make('Wheres', function () {
                    if ($this->taxonomyWheres()->count() > 0) {
                        return implode('<br/>', $this->taxonomyWheres()->pluck('name')->toArray());
                    }
                    return 'No Wheres';
                })->asHtml(),

                // Text::make('API', function () {
                //     return '<a href="/api/ec/poi/'.$this->id.'" target="_blank">[x]</a>';
                // })->asHtml(),
            ];
        }
    }

    public function fieldsForDetail(Request $request)
    {
        return [
            (new Tabs("EC Poi Details: {$this->name} ({$this->id})", [
                'Main' => [
                    Text::make('Geohub ID', function () {
                        return $this->id;
                    }),
                    Text::make('Author', function () {
                        return $this->author->name;
                    }),
                    DateTime::make('Created At')->onlyOnDetail(),
                    DateTime::make('Updated At')->onlyOnDetail(),
                    Number::make('OSM ID', 'osmid'),
                    NovaTabTranslatable::make([
                        Text::make(__('Name'), 'name'),
                        Textarea::make(__('Excerpt'), 'excerpt'),
                        Textarea::make('Description'),
                    ])->onlyOnDetail(),
                ],
                'Media' => [
                    // NovaTabTranslatable::make([
                    //     Text::make(__('Audio'),'audio')->onlyOnDetail(),
                    // ])->onlyOnDetail(),
                    Text::make(__('Audio'), 'audio')->onlyOnDetail(),
                    Text::make('Related Url', function () {
                        $out = '';
                        if (is_array($this->related_url) && count($this->related_url) > 0) {
                            foreach ($this->related_url as $label => $url) {
                                $out .= "<a href='{$url}' target='_blank'>{$label}</a></br>";
                            }
                        } else {
                            $out = "No related Url";
                        }
                        return $out;
                    })->asHtml(),
                    ExternalImage::make(__('Feature Image'), function () {
                        $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                        if ('' !== $url && substr($url, 0, 4) !== 'http') {
                            $url = Storage::disk('public')->url($url);
                        }

                        return $url;
                    })->withMeta(['width' => 400])->onlyOnDetail(),

                    // Text::make('Gallery',function(){
                    //     if (count($this->ecMedia) == 0) {
                    //         return 'No gallery';
                    //     }

                    //     $gallery = '';
                    //     foreach ($this->ecMedia as $media) {
                    //         $thumbnail = $media->thumbnail('150x150');
                    //         $gallery .= "<div class='w-3/4 py-4 break-words'><div><img src='$thumbnail' class='external-image-thumbnail'></div></div>";
                    //     }
                    //     return $gallery;
                    // })->asHtml(),
                ],
                'Map' => [
                    MapPointNova3::make(__('Map'), 'geometry')->withMeta([
                        'center' => ["51", "4"],
                        'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                        'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                        'minZoom' => 7,
                        'maxZoom' => 16,
                    ])
                ],

                'Style' => $this->style_tab(),

                'Info' => [
                    Boolean::make('Skip Geomixer Tech'),
                    Text::make('Contact Phone'),
                    Text::make('Contact Email'),
                    Text::make('Adress / complete', 'addr_complete'),
                    Text::make('Adress / street', 'addr_street'),
                    Text::make('Adress / housenumber', 'addr_housenumber'),
                    Text::make('Adress / postcode', 'addr_postcode'),
                    Text::make('Adress / locality', 'addr_locality'),
                    Text::make('Opening Hours'),
                    Number::Make('Elevation', 'ele'),
                    Text::make('Capacity'),
                    Text::make('Stars'),
                    Text::make('Code'),
                ],

                'Accessibility' => $this->accessibility_tab(),
                'Reachability' => $this->reachability_tab(),

                'Taxonomies' => [
                    Text::make('Poi Types', function () {
                        if ($this->taxonomyPoiTypes()->count() > 0) {
                            return implode(',', $this->taxonomyPoiTypes()->pluck('name')->toArray());
                        }
                        return 'No Poi Types';
                    }),
                    Text::make('Activities', function () {
                        if ($this->taxonomyActivities()->count() > 0) {
                            return implode(',', $this->taxonomyActivities()->pluck('name')->toArray());
                        }
                        return 'No activities';
                    }),
                    Text::make('Wheres', function () {
                        if ($this->taxonomyWheres()->count() > 0) {
                            return implode(',', $this->taxonomyWheres()->pluck('name')->toArray());
                        }
                        return 'No Wheres';
                    }),
                    Text::make('Themes', function () {
                        if ($this->taxonomyThemes()->count() > 0) {
                            return implode(',', $this->taxonomyThemes()->pluck('name')->toArray());
                        }
                        return 'No Themes';
                    }),
                    Text::make('Targets', function () {
                        if ($this->taxonomyTargets()->count() > 0) {
                            return implode(',', $this->taxonomyTargets()->pluck('name')->toArray());
                        }
                        return 'No Targets';
                    }),
                    Text::make('Whens', function () {
                        if ($this->taxonomyWhens()->count() > 0) {
                            return implode(',', $this->taxonomyWhens()->pluck('name')->toArray());
                        }
                        return 'No Whens';
                    }),
                ],
                'Data' => [
                    Heading::make($this->getData())->asHtml(),
                ],

            ]))->withToolbar(),

            // Necessary for view
            BelongsToMany::make('Gallery', 'ecMedia', 'App\Nova\EcMedia')->searchable()->nullable(),
        ];
    }

    public function fieldsForUpdate(Request $request)
    {

        try {
            $geojson = $this->model()->getGeojson();
        } catch (Exception $e) {
            $geojson = [];
        }

        $tab_title = "New EC Poi";
        // if(NovaCurrentResourceActionHelper::isUpdate($request)) {
        //     $tab_title = "EC Poi Edit: {$this->name} ({$this->id})";
        // }

        return [
            (new Tabs(
                $tab_title,
                [
                    'Main' => [
                        NovaTabTranslatable::make([
                            Text::make(__('Name'), 'name'),
                            Textarea::make(__('Excerpt'), 'excerpt'),
                            NovaTinymce5Editor::make('Description'),
                        ])->onlyOnForms(),
                        Number::make('OSM ID', 'osmid'),
                        BelongsTo::make('Author', 'author', User::class)->searchable()->canSee(function ($request) {
                            return $request->user()->can('Admin', $this);
                        }),
                    ],
                    'Media' => [
                        // TODO: not working with NovaTabTranslatable
                        // NovaTabTranslatable::make([
                        //     File::make(__('Audio'), 'audio')->store(function (Request $request, $model) {
                        //         return $model->uploadAudio($request->file());
                        //     })->acceptedTypes('audio/*')->onlyOnForms(),
                        // ]),

                        FeatureImagePopup::make(__('Feature Image (by map)'), 'featureImage')
                            ->onlyOnForms()
                            ->feature($geojson ?? [])
                            ->apiBaseUrl('/api/ec/poi/'),


                        EcMediaPopup::make(__('Gallery (by map)'), 'ecMedia')
                            ->onlyOnForms()
                            ->feature($geojson ?? [])
                            ->apiBaseUrl('/api/ec/poi/'),

                        KeyValue::make('Related Url')
                            ->keyLabel('Label')
                            ->valueLabel('Url with https://')
                            ->actionText('Add new related url')
                            ->rules('json'),
                    ],

                    'Style' => $this->style_tab(),

                    'Info' => [
                        Boolean::make('Skip Geomixer Tech')->help('Activate this option if the technical information should not be generated automatically.'),
                        Text::make('Adress / complete', 'addr_complete'),
                        Text::make('Adress / street', 'addr_street'),
                        Text::make('Adress / housenumber', 'addr_housenumber'),
                        Text::make('Adress / postcode', 'addr_postcode'),
                        Text::make('Adress / locality', 'addr_locality'),
                        Text::make('Opening Hours'),
                        Text::make('Contact Phone'),
                        Text::make('Contact Email'),
                        Number::Make('Elevation', 'ele'),
                        Text::make('Capacity'),
                        Text::make('Stars'),
                        Text::make('Code'),
                    ],

                    'Accessibility' => $this->accessibility_tab(),
                    'Reachability' => $this->reachability_tab(),

                    'Taxonomies' => [
                        AttachMany::make('TaxonomyPoiTypes')->showPreview(),
                        // AttachMany::make('TaxonomyWheres'),
                        AttachMany::make('TaxonomyActivities')->showPreview(),
                        AttachMany::make('TaxonomyTargets')->showPreview(),
                        // AttachMany::make('TaxonomyWhens'),
                        AttachMany::make('TaxonomyThemes')->showPreview(),
                    ],
                ]
            )),
            new Panel('Map / Geographical info', [
                MapPointNova3::make(__('Map'), 'geometry')->withMeta([
                    'center' => ["51", "4"],
                    'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                    'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                    'minZoom' => 7,
                    'maxZoom' => 16,
                ])
            ]),

            // Do not remove below code, necessary for Edit mode  
            BelongsToMany::make('Gallery', 'ecMedia', 'App\Nova\EcMedia')->searchable()->nullable(),

        ];
    }

    public function fieldsForCreate(Request $request)
    {
        return $this->fieldsForUpdate($request);
    }

    private function style_tab()
    {
        return [
            Swatches::make(__('Color'), 'color')
                ->default('#de1b0d')
                ->colors('text-advanced')->withProps([
                    'show-fallback' => true,
                    'fallback-type' => 'input',
                ])->hideFromIndex(),
            Text::make(__('Z index'), 'zindex'),
            Toggle::make(__('No Interaction'), 'noInteraction')
                ->trueValue('On')
                ->falseValue('Off'),
            Toggle::make(__('No Details'), 'noDetails')
                ->trueValue('On')
                ->falseValue('Off'),
        ];
    }

    private function accessibility_tab()
    {
        return [
            DateTime::make(__('Last verification date'), 'accessibility_validity_date'),
            File::make(__('Accessibility PDF'), 'accessibility_pdf')->disk('public')
                ->acceptedTypes('.pdf'),

            Toggle::make(__('Access Mobility Check'), 'access_mobility_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Select::make(__('Access Mobility Level'), 'access_mobility_level')->options([
                'accessible_independently' => 'Accessible independently',
                'accessible_with_assistance' => 'Accessible with assistance',
                'accessible_with_a_guide' => 'Accessible with a guide',
            ]),
            Textarea::make(__('Access Mobility Desription'), 'access_mobility_description'),

            Toggle::make(__('Access Hearing Check'), 'access_hearing_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Select::make(__('Access Hearing Level'), 'access_hearing_level')->options([
                'accessible_independently' => 'Accessible independently',
                'accessible_with_assistance' => 'Accessible with assistance',
                'accessible_with_a_guide' => 'Accessible with a guide',
            ]),
            Textarea::make(__('Access Hearing Desription'), 'access_hearing_description'),

            Toggle::make(__('Access Vision Check'), 'access_vision_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Select::make(__('Access Vision Level'), 'access_vision_level')->options([
                'accessible_independently' => 'Accessible independently',
                'accessible_with_assistance' => 'Accessible with assistance',
                'accessible_with_a_guide' => 'Accessible with a guide',
            ]),
            Textarea::make(__('Access Vision Desription'), 'access_vision_description'),

            Toggle::make(__('Access Cognitive Check'), 'access_cognitive_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Select::make(__('Access Cognitive Level'), 'access_cognitive_level')->options([
                'accessible_independently' => 'Accessible independently',
                'accessible_with_assistance' => 'Accessible with assistance',
                'accessible_with_a_guide' => 'Accessible with a guide',
            ]),
            Textarea::make(__('Access Cognitive Desription'), 'access_cognitive_description'),

            Toggle::make(__('Access Food Check'), 'access_food_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Textarea::make(__('Access Food Desription'), 'access_food_description'),
        ];
    }

    private function reachability_tab()
    {
        return [
            Toggle::make(__('Reachability by Bike'), 'reachability_by_bike_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Textarea::make(__('Reachability by Bike Description'), 'reachability_by_bike_description'),

            Toggle::make(__('Reachability on Foot'), 'reachability_on_foot_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Textarea::make(__('Reachability on Foot Description'), 'reachability_on_foot_description'),

            Toggle::make(__('Reachability by Car'), 'reachability_by_car_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Textarea::make(__('Reachability by Car Description'), 'reachability_by_car_description'),

            Toggle::make(__('Reachability by Public Transportation'), 'reachability_by_public_transportation_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Textarea::make(__('Reachability by Public Transportation Description'), 'reachability_by_public_transportation_description'),
        ];
    }

    /**
     * This method returns the HTML STRING rendered by DATA tab (object structure and fields)
     * Refers to OFFICIAL DOCUMENTATION:
     * https://docs.google.com/spreadsheets/d/1S5kVk2tBF4ZQxuaeYBLG2lLu8Y8AnfmKzvHft8Pw7ms/edit#gid=0
     *
     * @return string
     */
    public function getData(): string
    {
        $text = <<<HTML
        <style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}
</style>
<table>
<tr><th>GROUP</th><th>NAME</th><th>TYPE</th><th>NULL</th><th>DEF</th><th>FK</th><th>I18N</th><th>LABEL</th><th>DESCRIPTION</th></tr>
<tr><td><i>main</i></td><td>id</td><td>int8</td><td>NO</td><td>AUTO</td><td>-</td><td>NO</td><td>Geohub ID</td><td>POI identification code in the Geohub</td></tr>
<tr><td><i>main</i></td><td>user_id</td><td>int4</td><td>NO</td><td>NULL</td><td>users</td><td>NO</td><td>Author</td><td>POI author: foreign key wiht table users</td></tr>
<tr><td><i>main</i></td><td>created_at</td><td>timestamp(0)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Created At</td><td>When POI has been created: datetime</td></tr>
<tr><td><i>main</i></td><td>updated_at</td><td>timestamp(0)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Updated At</td><td>When POI has been modified last time: datetime</td></tr>
<tr><td><i>main</i></td><td>name</td><td>text</td><td>NO</td><td>NULL</td><td>-</td><td>YES</td><td>Name</td><td>Name of the POI, also know as title</td></tr>
<tr><td><i>main</i></td><td>description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Description</td><td>Descrption of the POI</td></tr>
<tr><td><i>main</i></td><td>excerpt</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Excerpt</td><td>Short Description of the POI</td></tr>
<tr><td><i>-</i></td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>media</i></td><td>audio</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>NO*</td><td>Audio</td><td>Audio file associated to the POI: tipically is the description text2speach</td></tr>
<tr><td><i>media</i></td><td>related_url</td><td>json</td><td>YES</td><td>NULL</td><td>-</td><td>NO*</td><td>Related Url</td><td>List (label->url) of URL associated to the POI</td></tr>
<tr><td><i>media</i></td><td>feature_image</td><td>int4</td><td>YES</td><td>NULL</td><td>ec_media</td><td>NO</td><td>Feature Image</td><td>Main image representig the POI: foreign key with ec_media</td></tr>
<tr><td><i>-</i></td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>map</i></td><td>geometry</td><td>geometry</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Map</td><td>The POI geometry (linestring, 3D)</td></tr>
<tr><td><i>-</i></td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>info</i></td><td>contact_phone</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Contact Phone</td><td>Contact Info: phone (+XX XXX XXXXX)</td></tr>
<tr><td><i>info</i></td><td>contact_email</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Contact Email</td><td>Contact info: email (xxx@xxx.xx)</td></tr>
<tr><td><i>info</i></td><td>addr_street</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Address / Street</td><td>Contact Info: address name of the street</td></tr>
<tr><td><i>info</i></td><td>addr_housenumber</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Address / Housenumber</td><td>Contact Info: address housenumber</td></tr>
<tr><td><i>info</i></td><td>addr_postcode</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Address / Postcode</td><td>Contact Info: address postcode</td></tr>
<tr><td><i>info</i></td><td>addr_locality</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Address / Locality</td><td>Contact Info: address locality</td></tr>
<tr><td><i>info</i></td><td>opening_hours</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Opening Hours</td><td>Contact Info: Opening hours, using OSM syntax https://wiki.openstreetmap.org/wiki/Key:opening_hours</td></tr>
<tr><td><i>info</i></td><td>ele</td><td>float8</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Elevation</td><td>Elevation of the POI (meter)</td></tr>
<tr><td><i>info</i></td><td>capacity</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Capacity</td><td>In the case of accommodation facilities, it indicates the number of beds available</td></tr>
<tr><td><i>info</i></td><td>stars</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Stars</td><td>In the case of accommodation facilities with a star classification system, it indicates the number of stars (or equivalent) of the POI</td></tr>
<tr><td><i>info</i></td><td>code</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Tracking Code</td><td>POI code (code related to the Tour Operator package)</td></tr>
<tr><td><i>-</i></td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>outsource</i></td><td>out_source_feature_id</td><td>int8</td><td>YES</td><td>NULL</td><td>out_source_features</td><td>NO</td><td>Out Source Feature</td><td>Out Source connected to the POI</td></tr>
<tr><td><i>-</i></td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>style</i></td><td>color</td><td>int4</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Color</td><td>The color of the poi on the map</td></tr>
<tr><td><i>style</i></td><td>zindex</td><td>int4</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Z-index</td><td>z-index of the element</td></tr>
<tr><td><i>style</i></td><td>noInteraction</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>No Interaction</td><td>Indicates if an object on the map does any interactions upon click</td></tr>
<tr><td><i>style</i></td><td>noDetails</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>No Details</td><td>For objects with no_interaction = FALSE (default) it indicates whether the detail tab can be opened for this object</td></tr>
<tr><td><i>-</i></td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>accessibility</i></td><td>accessibility_validity_date</td><td>timestamp(0)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Last verification date</td><td>last date of verification of the accuracy of the information</td></tr>
<tr><td><i>accessibility</i></td><td>accessibility_pdf</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>SI</td><td>Accessibility PDF</td><td>A generic PDF for people with disabilities who log in so that they have the opportunity to have an initial generic information and</td></tr>
<tr><td><i>accessibility</i></td><td>access_mobility_check</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Access Mobility Check</td><td>Accessibility for motor disabilities: if the value is true it indicates that the instance is accessible</td></tr>
<tr><td><i>accessibility</i></td><td>access_mobility_description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>SI</td><td>Access Mobility Description</td><td>Field for the detailed description of the characteristics of the application from the point of view of accessibility for motor disabilities</td></tr>
<tr><td><i>accessibility</i></td><td>access_mobility_level</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Access Mobility Level</td><td>Field to select the degree of accessibility</td></tr>
<tr><td><i>accessibility</i></td><td>access_hearing_check</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Access Hearing Check</td><td>Accessibility for hearing impairments: if the value is true it indicates that the instance is accessible</td></tr>
<tr><td><i>accessibility</i></td><td>access_hearing_description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>SI</td><td>Access Hearing Description</td><td>Field for the detailed description of the characteristics of the application from the point of view of accessibility for hearing impairments</td></tr>
<tr><td><i>accessibility</i></td><td>access_hearing_level</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Access Hearing Level</td><td>Field for selecting the degree of hearing</td></tr>
<tr><td><i>accessibility</i></td><td>access_vision_check</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Access Vision Check</td><td>Accessibility for visual disabilities: if the value is true it indicates that the instance is accessible</td></tr>
<tr><td><i>accessibility</i></td><td>access_vision_description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>SI</td><td>Access Vision Description</td><td>Field for the detailed description of the characteristics of the application from the point of view of accessibility for visual disabilities</td></tr>
<tr><td><i>accessibility</i></td><td>access_vision_level</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Access Vision Level</td><td>Field for selecting the degree of view</td></tr>
<tr><td><i>accessibility</i></td><td>access_cognitive_check</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Access Cognitive Check</td><td>Accessibility for cognitive disabilities: if the value is true it indicates that the instance is accessible</td></tr>
<tr><td><i>accessibility</i></td><td>access_cognitive_description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>SI</td><td>Access Cognitive Description</td><td>Field for the detailed description of the characteristics of the application from the point of view of accessibility for cognitive disabilities</td></tr>
<tr><td><i>accessibility</i></td><td>access_cognitive_level</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Access Cognitive Level</td><td>Field to select the degree of cognitive</td></tr>
<tr><td><i>accessibility</i></td><td>access_food_check</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Access Food Check</td><td>Accessibility for food-related disabilities: if the value is true it indicates that the instance is accessible</td></tr>
<tr><td><i>accessibility</i></td><td>access_food_description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>SI</td><td>Access Food Description</td><td>Field for the detailed description of the characteristics of the application from the point of view of accessibility for type XXX disabilities (food)</td></tr>
<tr><td><i>accessibility</i></td><td>reachability_by_bike_check</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Reachability by Bike Check</td><td>Accessibility by bicycle: if the value is true, it indicates that the instance can be reached by bicycle</td></tr>
<tr><td><i>accessibility</i></td><td>reachability_by_bike_description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Reachability by Bike Description</td><td>Field for a detailed description of the characteristics of the application from the point of view of accessibility by bicycle</td></tr>
<tr><td><i>accessibility</i></td><td>reachability_on_foot_check</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Reachability on Foot Check</td><td>Reachability on foot: if the value is true it indicates that the instance can be reached freely</td></tr>
<tr><td><i>accessibility</i></td><td>reachability_on_foot_description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Reachability on Foot Description</td><td>Field for a detailed description of the characteristics of the instance from the point of view of reachability on foot</td></tr>
<tr><td><i>accessibility</i></td><td>reachability_by_car_check</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Reachability by Car Check</td><td>Reachability with on foot: if the value is true it indicates that the instance can be reached by car</td></tr>
<tr><td><i>accessibility</i></td><td>reachability_by_car_description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Reachability by Car Description</td><td>Field for the detailed description of the characteristics of the instance from the point of view of accessibility with the machine</td></tr>
"<tr><td><i>accessibility</i></td><td>reachability_by_public_transportation_check</td><td>bool</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Reachability by Public Transportation Check</td><td>Accessibility by public transport: if the value is true, it indicates that the instance can be reached by public transport
</td></tr>"
<tr><td><i>accessibility</i></td><td>reachability_by_public_transportation_description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Reachability by Public Transportation Description</td><td>Field for the detailed description of the characteristics of the application from the point of view of accessibility by public transport</td></tr>

</table>
HTML;
        return $text;
    }

    /**
     * Get the cards available for the request.
     *
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function filters(Request $request)
    {
        if ($request->user()->hasRole('Editor')) {
            return [
                new PoiSearchableFromOSMID,
                new HasFeatureImage,
                new HasImageGallery,
                new SelectFromThemesPoi,
                new SelectFromWheresPoi,
                new SelectFromPoiTypesPoi
            ];
        }
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            new RegenerateEcPoi(),
            (new DownloadExcelEcPoiAction)->allFields()->except('geometry')->withHeadings(),
        ];
    }
}
