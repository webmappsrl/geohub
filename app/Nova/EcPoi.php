<?php

namespace App\Nova;

use App\Nova\Actions\BulkEditPOIColorAction;
use App\Nova\Actions\BulkEditThemesEcResourceAction;
use App\Nova\Actions\DownloadExcelEcPoiAction;
use App\Nova\Actions\DownloadPoiTypesTaxonomiesAction;
use App\Nova\Actions\RegenerateEcPoi;
use App\Nova\Actions\UploadPoiFile;
use App\Nova\Fields\NovaWyswyg;
use App\Nova\Filters\HasFeatureImage;
use App\Nova\Filters\HasImageGallery;
use App\Nova\Filters\PoiSearchableFromOSMID;
use App\Nova\Filters\SelectFromPoiTypesPoi;
use App\Nova\Filters\SelectFromThemesPoi;
use App\Nova\Filters\SelectFromWheresPoi;
use Chaseconey\ExternalImage\ExternalImage;
use Davidpiesse\NovaToggle\Toggle;
use Eminiarts\Tabs\Tabs;
use Eminiarts\Tabs\TabsOnEdit;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\KeyValue;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use NovaAttachMany\AttachMany;
use Titasgailius\SearchRelations\SearchesRelations;
use Webmapp\EcMediaPopup\EcMediaPopup;
use Webmapp\FeatureImagePopup\FeatureImagePopup;
use Wm\MapPointNova3\MapPointNova3;
use Yna\NovaSwatches\Swatches;

class EcPoi extends Resource
{
    use SearchesRelations;
    use TabsOnEdit;

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
                    return '<a href="/api/ec/poi/'.$this->id.'" target="_blank">[x]</a>';
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
                    })->help(__('The unique identifier for the POI in Geohub.')),
                    Text::make('Author', function () {
                        return $this->author->name;
                    })->help(__('The user who created the POI, affecting the content publication for associated apps.')),
                    DateTime::make('Created At')
                        ->onlyOnDetail()
                        ->help(__('The date and time when the POI was created.')),
                    DateTime::make('Updated At')
                        ->onlyOnDetail()
                        ->help(__('The date and time when the POI was last modified.')),
                    Number::make('OSM ID', 'osmid')
                        ->help(__('The OpenStreetMap ID associated with the POI. This ID cannot be modified once set, as the data will synchronize with OSM.')),
                    Number::make('OUT SOURCE FEATURE ID', 'out_source_feature_id')
                        ->canSee(function ($request) {
                            return $request->user()->can('Admin', $this);
                        })
                        ->help(__('If this field contains data, updates to various fields will not be processed as they are synchronized from a different API. Remove the data in this field to update the fields.')),
                    Heading::make(
                        <<<'HTML'
                            <ul>
                                <li><p><strong>Name</strong>: The name of the POI, also known as the title.</p></li>
                                <li><p><strong>Excerpt</strong>: A brief summary or introduction for the POI, displayed in lists or previews.</p></li>
                                <li><p><strong>Description</strong>: A detailed description of the POI, providing comprehensive information.</p></li>
                                <li><p><strong>Info</strong>: Additional information about the POI, displayed in the POI detail.</p></li>
                                <li><p><strong>Embedded HTML</strong>: Add custom html code, displayed in the POI detail.</p></li>
                            </ul>
                            HTML
                    )->asHtml(),
                    NovaTabTranslatable::make([
                        Text::make(__('Name'), 'name'),
                        Textarea::make(__('Excerpt'), 'excerpt'),
                        Textarea::make('Description'),
                        Textarea::make(__('Info'), 'info'), // TODO: ADD2WMPACKAGE
                        Textarea::make(__('Embedded HTML'), 'embedded_html'), // TODO: ADD2WMPACKAGE
                    ])->onlyOnDetail(),
                ],
                'Media' => [
                    // NovaTabTranslatable::make([
                    //     Text::make(__('Audio'),'audio')->onlyOnDetail(),
                    // ])->onlyOnDetail(),
                    Text::make(__('Audio'), 'audio')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating()
                        ->help(__('The audio file associated with the POI, typically used for text-to-speech descriptions.')),
                    Text::make('Related Url', function () {
                        $out = '';
                        $help = __('<p>Related Url: List of URLs associated with the POI, each with a label for display.</p>');
                        if (is_array($this->related_url) && count($this->related_url) > 0) {
                            foreach ($this->related_url as $label => $url) {
                                $out .= "<a href='{$url}' target='_blank'>{$label}</a></br>";
                            }
                        } else {
                            $out = 'No related Url';
                        }

                        return $out.$help;
                    })->asHtml(),
                    ExternalImage::make(__('Feature Image'), function () {
                        $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                        if ($url !== '' && substr($url, 0, 4) !== 'http') {
                            $url = Storage::disk('public')->url($url);
                        }

                        return $url;
                    })
                        ->withMeta(['width' => 400])
                        ->onlyOnDetail()
                        ->help(__('Feature Image: The main image representing the POI, ideally in horizontal format (1440 x 500 pixels).')),
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
                    Heading::make('<p>Map: The geographical representation of the POI on the map.</p>')->asHtml(),
                    MapPointNova3::make(__('Map'), 'geometry')->withMeta([
                        'center' => ['51', '4'],
                        'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                        'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                        'minZoom' => 7,
                        'maxZoom' => 16,
                    ]),
                ],
                'Style' => $this->style_tab(),
                'Info' => [
                    Boolean::make('Skip Geomixer Tech')
                        ->help(__('Enable this option if you do not want the Elevation data to be generated automatically. This way, you can enter them manually.')),
                    Text::make('Contact Phone')
                        ->help(__('The contact phone number associated with the POI.')),
                    Text::make('Contact Email')
                        ->help(__('The contact email address associated with the POI.')),
                    Text::make('Adress / complete', 'addr_complete')
                        ->help(__('Adress / complete: The complete address for the POI.')),
                    Text::make('Adress / street', 'addr_street')
                        ->help(__('Adress / street: The street part of the address.')),
                    Text::make('Adress / housenumber', 'addr_housenumber')
                        ->help(__('Adress / housenumber: The house number part of the address.')),
                    Text::make('Adress / postcode', 'addr_postcode')
                        ->help(__('Adress / postcode: The postal code part of the address.')),
                    Text::make('Adress / locality', 'addr_locality')
                        ->help(__('Adress / locality: The locality part of the address.')),
                    Text::make('Opening Hours')
                        ->help(__('Opening Hours: The opening hours of the POI, following OSM syntax.')),
                    Number::Make('Elevation', 'ele')
                        ->help(__('The elevation of the POI in meters, displayed in the POI detail. To modify it manually, enable the "Skip Geomixer Tech" option')),
                    Text::make('Capacity')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Text::make('Stars')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
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
                    })->help(__('The taxonomy types associated with the POI')),
                    Text::make('Activities', function () {
                        if ($this->taxonomyActivities()->count() > 0) {
                            return implode(',', $this->taxonomyActivities()->pluck('name')->toArray());
                        }

                        return 'No activities';
                    })
                        ->help(__('The taxonomy activities associated with the POI')),
                    Text::make('Wheres', function () {
                        if ($this->taxonomyWheres()->count() > 0) {
                            return implode(',', $this->taxonomyWheres()->pluck('name')->toArray());
                        }

                        return 'No Wheres';
                    })
                        ->help(__('The taxonomy locations associated with the POI')),
                    Text::make('Themes', function () {
                        if ($this->taxonomyThemes()->count() > 0) {
                            return implode(',', $this->taxonomyThemes()->pluck('name')->toArray());
                        }

                        return 'No Themes';
                    })
                        ->help(__('The taxonomy themes associated with the POI')),
                    Text::make('Targets', function () {
                        if ($this->taxonomyTargets()->count() > 0) {
                            return implode(',', $this->taxonomyTargets()->pluck('name')->toArray());
                        }

                        return 'No Targets';
                    })
                        ->help(__('The taxonomy targets associated with the POI')),
                    Text::make('Whens', function () {
                        if ($this->taxonomyWhens()->count() > 0) {
                            return implode(',', $this->taxonomyWhens()->pluck('name')->toArray());
                        }

                        return 'No Whens';
                    })
                        ->help(__('The taxonomy periods associated with the POI')),
                ],
                'Data' => [
                    Heading::make($this->getData())->asHtml(),
                ],
            ]))->withToolbar(),

            // Necessary for view
            BelongsToMany::make('Gallery', 'ecMedia', 'App\Nova\EcMedia')->searchable()->nullable(),
            BelongsToMany::make('Tracks', 'ecTracks', 'App\Nova\EcTrack')->searchable()->nullable(),
        ];
    }

    public function fieldsForUpdate(Request $request)
    {

        try {
            $geojson = $this->model()->getGeojson();
        } catch (Exception $e) {
            $geojson = [];
        }

        $tab_title = 'New EC Poi';
        // if(NovaCurrentResourceActionHelper::isUpdate($request)) {
        //     $tab_title = "EC Poi Edit: {$this->name} ({$this->id})";
        // }
        $osmid = $this->model()->osmid;
        $isOsmidSet = ! is_null($osmid);

        return [
            (new Tabs(
                $tab_title,
                [
                    'Main' => [
                        Heading::make(
                            <<<'HTML'
                            <ul>
                                <li><p><strong>Name</strong>: Enter the name of the POI. This will be the main title displayed.</p></li>
                                <li><p><strong>Excerpt</strong>: Provide a brief summary or introduction. This will be shown in lists or previews.</p></li>
                                <li><p><strong>Description</strong>: Add a detailed description. This field is for the full content that users will see.</p></li>
                                <li><p><strong>Info</strong>: Additional information about the POI, displayed in the POI detail.</p></li>
                                <li><p><strong>Embedded HTML</strong>: Add custom html code, displayed in the POI detail.</p></li>
                            </ul>
                            HTML
                        )->asHtml()->onlyOnForms(),
                        NovaTabTranslatable::make([
                            Text::make(__('Name'), 'name')
                                ->readonly($isOsmidSet)
                                ->help(__($isOsmidSet ? 'This field is not editable because the OSM ID is already set.' : 'Displayed name of the POI.')),
                            Textarea::make(__('Excerpt'), 'excerpt')
                                ->rules('nullable', 'max:255')
                                ->help(_('Provide a brief summary or excerpt for the POI. This should be a concise description.')),
                            NovaWyswyg::make('Description')->canSee(function () use ($osmid) {
                                return is_null($osmid);
                            })
                                ->help(__('Enter a detailed description of the POI. Use this field to provide comprehensive information.')),
                            NovaWyswyg::make(__('Info'), 'info') // TODO: ADD2WMPACKAGE
                                ->help(__('Enter additional information of the POI.')),
                            NovaWyswyg::make(__('Embedded HTML'), 'embedded_html') // TODO: ADD2WMPACKAGE
                                ->canSee(function () {
                                    return $this->canShowEmbeddedHtml();
                                })
                                ->help(__('Enter a html code.')),
                        ])->onlyOnForms(),
                        Number::make('OSM ID', 'osmid')
                            ->help(__('OpenStreetMap ID associated with the track: once applied, it is not possible to modify data here in GeoHub as they will be synchronized with OSM')),
                        Number::make('OUT SOURCE FEATURE ID', 'out_source_feature_id')
                            ->canSee(function ($request) {
                                return $request->user()->can('Admin', $this);
                            })
                            ->help(__('If there is data in this field, updates in the various fields will not be processed because they are synchronized from a different API. Remove the data in this field to update the fields.')),
                        BelongsTo::make('Author', 'author', User::class)->searchable()->canSee(function ($request) {
                            return $request->user()->can('Admin', $this);
                        })
                            ->help(__('Author who created the POI: this affects the publication of the content, which will be available for apps associated with the indicated author')),
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
                            ->apiBaseUrl('/api/ec/poi/')
                            ->help(__('The feature image is displayed in the list of POIs. It is advisable to use a high-quality image in horizontal format 1440 x 500 pixels in jpg or png format. Alternatively, GeoHub will automatically create thumbnails and crop the image to fit. If you have uploaded georeferenced images in the Ec Medias section that match the track\'s location, you will find them by clicking on "Select image" in the first tab "Associated images". Otherwise, you can upload an image directly via the "Upload media" tab. If set, the feature image of the POI will be shown as the first image in the gallery.')),

                        EcMediaPopup::make(__('Gallery (by map)'), 'ecMedia')
                            ->onlyOnForms()
                            ->feature($geojson ?? [])
                            ->apiBaseUrl('/api/ec/poi/')
                            ->help(__('The Gallery can include multiple images of different sizes in jpg or png format. For better visualization, it is advisable to use the same size for all images in the gallery. If you have uploaded georeferenced images in the Ec Medias section that match the POIS\'s location, you will find them by clicking on "Select image" in the first tab "Associated images". Otherwise, you can upload an image directly via the "Upload media" tab.')),

                        KeyValue::make('Related Url')
                            ->keyLabel('Label')
                            ->valueLabel('Url with https://')
                            ->actionText('Add new related url')
                            ->rules('json')
                            ->help(__('Here you can enter URLs to be displayed in the POI detail view. In the label, you can enter the text to be displayed, e.g., "website.com". In the URL field with https, enter the full URL, e.g., "https://website.com/". It is possible to enter multiple URLs.')),
                    ],

                    'Style' => $this->style_tab(),

                    'Info' => [
                        Boolean::make('Skip Geomixer Tech')
                            ->help(__('Enable this option if you do not want the Elevation data to be generated automatically. This way, you can enter them manually.')),
                        Text::make('Adress / complete', 'addr_complete')
                            ->help(__('Enter the full address.')),
                        Text::make('Address / housenumber', 'addr_housenumber')
                            ->help(__('Enter the house number of the address.')),
                        Text::make('Address / postcode', 'addr_postcode')
                            ->help(__('Enter the postal code of the address.')),
                        Text::make('Address / locality', 'addr_locality')
                            ->help(__('Enter the locality of the address.')),
                        Text::make('Opening Hours')
                            ->help(__('Enter the opening hours.')),
                        Text::make('Contact Phone')
                            ->help(__('Enter the contact phone number.')),
                        Text::make('Contact Email')
                            ->help(__('Enter the contact email address.')),
                        Number::make('Elevation', 'ele')
                            ->readonly($isOsmidSet)
                            ->help(__($isOsmidSet ? 'This field is not editable because the OSM ID is already set.' : 'Elevation data displayed in the POI detail. To modify it manually, enable the option at the top of this page "Skip Geomixer Tech".')),
                        Text::make('Capacity')
                            ->hideFromIndex()
                            ->hideFromDetail()
                            ->hideWhenCreating()
                            ->hideWhenUpdating(),
                        Text::make('Stars')
                            ->hideFromIndex()
                            ->hideFromDetail()
                            ->hideWhenCreating()
                            ->hideWhenUpdating(),
                        Text::make('Code')
                            ->hideFromIndex()
                            ->hideFromDetail()
                            ->hideWhenCreating()
                            ->hideWhenUpdating(),
                    ],
                    'Accessibility' => $this->accessibility_tab(),
                    'Reachability' => $this->reachability_tab(),
                    'Taxonomies' => [
                        AttachMany::make('TaxonomyPoiTypes')
                            ->showPreview()
                            ->help(__('Select one or more POI types taxonomies to associate with the POI. Click "Preview" to display the selected ones.')),
                        // AttachMany::make('TaxonomyWheres'),
                        AttachMany::make('TaxonomyActivities')
                            ->showPreview()
                            ->help(__('Select one or more activities taxonomies to associate with the POI. Click "Preview" to display the selected ones.')),
                        AttachMany::make('TaxonomyTargets')
                            ->showPreview()
                            ->help(__('Select one or more targets taxonomies to associate with the POI. Click "Preview" to display the selected ones.')),
                        // AttachMany::make('TaxonomyWhens'),
                        AttachMany::make('TaxonomyThemes')
                            ->showPreview()
                            ->help(__('Select one or more activities taxonomies to associate with the POI. Click "Preview" to display the selected ones.')),
                    ],
                ]
            )),
            new Panel('Map / Geographical info', [
                MapPointNova3::make(__('Map'), 'geometry')->withMeta([
                    'center' => ['51', '4'],
                    'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                    'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                    'minZoom' => 7,
                    'maxZoom' => 16,
                ]),
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
                ->colors(['#f0f8ff', '#faebd7', '#00ffff', '#7fffd4', '#f0ffff', '#f5f5dc', '#ffe4c4', '#000000', '#ffebcd', '#0000ff', '#8a2be2', '#a52a2a', '#deb887', '#5f9ea0', '#7fff00', '#d2691e', '#ff7f50', '#6495ed', '#fff8dc', '#dc143c', '#00008b', '#008b8b', '#b8860b', '#a9a9a9', '#006400', '#bdb76b', '#8b008b', '#556b2f', '#ff8c00', '#9932cc', '#8b0000', '#e9967a', '#8fbc8f', '#483d8b', '#2f4f4f', '#00ced1', '#9400d3', '#ff1493', '#00bfff', '#696969', '#1e90ff', '#b22222', '#fffaf0', '#228b22', '#ff00ff', '#dcdcdc', '#f8f8ff', '#daa520', '#ffd700', '#808080', '#008000', '#adff2f', '#f0fff0', '#ff69b4', '#cd5c5c', '#4b0082', '#fffff0', '#f0e68c', '#fff0f5', '#e6e6fa', '#7cfc00', '#fffacd', '#add8e6', '#f08080', '#e0ffff', '#fafad2', '#d3d3d3', '#90ee90', '#ffb6c1', '#ffa07a', '#20b2aa', '#87cefa', '#778899', '#b0c4de', '#ffffe0', '#00ff00', '#32cd32', '#faf0e6', '#800000', '#66cdaa', '#0000cd', '#ba55d3', '#9370db', '#3cb371', '#7b68ee', '#00fa9a', '#48d1cc', '#c71585', '#191970', '#f5fffa', '#ffe4e1', '#ffe4b5', '#ffdead', '#000080', '#fdf5e6', '#808000', '#6b8e23', '#ffa500', '#ff4500', '#da70d6', '#eee8aa', '#98fb98', '#afeeee', '#db7093', '#ffefd5', '#ffdab9', '#cd853f', '#ffc0cb', '#dda0dd', '#b0e0e6', '#800080', '#663399', '#ff0000', '#bc8f8f', '#4169e1', '#8b4513', '#fa8072', '#f4a460', '#2e8b57', '#fff5ee', '#a0522d', '#c0c0c0', '#87ceeb', '#6a5acd', '#708090', '#fffafa', '#00ff7f', '#4682b4', '#d2b48c', '#008080', '#d8bfd8', '#ff6347', '#40e0d0', '#ee82ee', '#f5deb3', '#ffffff', '#f5f5f5', '#ffff00', '#9acd32'])
                ->hideFromIndex()
                ->help(__('Choose a color to associate with the individual track.')),
            // Swatches::make(__('Color'), 'color')
            //     ->default('#de1b0d')
            //     ->colors('text-advanced')->withProps([
            //         'show-fallback' => true,
            //         'fallback-type' => 'input',
            //     ])->hideFromIndex(),
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
            Textarea::make(__('Access Mobility Description'), 'access_mobility_description'),

            Toggle::make(__('Access Hearing Check'), 'access_hearing_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Select::make(__('Access Hearing Level'), 'access_hearing_level')->options([
                'accessible_independently' => 'Accessible independently',
                'accessible_with_assistance' => 'Accessible with assistance',
                'accessible_with_a_guide' => 'Accessible with a guide',
            ]),
            Textarea::make(__('Access Hearing Description'), 'access_hearing_description'),

            Toggle::make(__('Access Vision Check'), 'access_vision_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Select::make(__('Access Vision Level'), 'access_vision_level')->options([
                'accessible_independently' => 'Accessible independently',
                'accessible_with_assistance' => 'Accessible with assistance',
                'accessible_with_a_guide' => 'Accessible with a guide',
            ]),
            Textarea::make(__('Access Vision Description'), 'access_vision_description'),

            Toggle::make(__('Access Cognitive Check'), 'access_cognitive_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Select::make(__('Access Cognitive Level'), 'access_cognitive_level')->options([
                'accessible_independently' => 'Accessible independently',
                'accessible_with_assistance' => 'Accessible with assistance',
                'accessible_with a guide' => 'Accessible with a guide',
            ]),
            Textarea::make(__('Access Cognitive Description'), 'access_cognitive_description'),

            Toggle::make(__('Access Food Check'), 'access_food_check')
                ->trueValue('On')
                ->falseValue('Off'),
            Textarea::make(__('Access Food Description'), 'access_food_description'),
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

    // TODO: ADD2WMPACKAGE
    private function canShowEmbeddedHtml(): bool
    {
        $user = $this->author;
        if (! $user) {
            return false;
        }

        $apps = $user->apps;
        if ($apps->count() == 1) {
            return $apps->first()->show_embedded_html;
        } else {
            foreach ($apps as $app) {
                if ($app->show_embedded_html && $app->ecPoiInApp($this->id)) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * This method returns the HTML STRING rendered by DATA tab (object structure and fields)
     * Refers to OFFICIAL DOCUMENTATION:
     * https://docs.google.com/spreadsheets/d/1S5kVk2tBF4ZQxuaeYBLG2lLu8Y8AnfmKzvHft8Pw7ms/edit#gid=0
     */
    public function getData(): string
    {
        $text = <<<'HTML'
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
                new SelectFromPoiTypesPoi,
            ];
        }

        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
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
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            new RegenerateEcPoi,
            new BulkEditThemesEcResourceAction,
            new BulkEditPOIColorAction,
            (new DownloadExcelEcPoiAction)->allFields()->except('geometry')->withHeadings(),
            (new DownloadPoiTypesTaxonomiesAction)->standalone(),
            (new UploadPoiFile)->standalone(),
        ];
    }
}
