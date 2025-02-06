<?php

namespace App\Nova;

use App\Helpers\NovaCurrentResourceActionHelper;
use App\Models\TaxonomyWhere;
use App\Nova\Actions\BulkEditThemesEcResourceAction;
use App\Nova\Actions\CreateTracksWithOSMIDAction;
use App\Nova\Actions\DownloadExcelEcTrackAction;
use App\Nova\Actions\EnrichDataFromDemOsm;
use App\Nova\Actions\RegenerateEcTrack;
use App\Nova\Actions\ReverseEcTrackGeomtryAction;
use App\Nova\Actions\UpdateEcTracks3DDEMAction;
use App\Nova\Actions\UpdateEcTracksDEMAction;
use App\Nova\Actions\UploadTrackFile;
use App\Nova\Fields\NovaWyswyg;
use App\Nova\Filters\HasFeatureImage;
use App\Nova\Filters\HasImageGallery;
use App\Nova\Filters\SearchableFromOSMID;
use App\Nova\Filters\SelectFromActivities;
use App\Nova\Filters\SelectFromThemesTrack;
use App\Nova\Filters\SelectFromWheresTrack;
use Chaseconey\ExternalImage\ExternalImage;
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
use Webmapp\Ecpoipopup\Ecpoipopup;
use Webmapp\FeatureImagePopup\FeatureImagePopup;
use Wm\MapMultiLinestringNova3\MapMultiLinestringNova3;
use Yna\NovaSwatches\Swatches;

class EcTrack extends Resource
{
    use SearchesRelations;
    use TabsOnEdit;

    /**
     * The model the resource corresponds to.
     */
    public static string $model = \App\Models\EcTrack::class;

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
        'ref',
        'osmid',
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
     */
    public function fields(Request $request): array
    {

        return [
            ID::make('id'),
            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name'),
            ]),
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
                Text::make('Name', function () {
                    return implode('<br />', explode("\n", wordwrap($this->name), 50));
                })->asHtml(),
                BelongsTo::make('Author', 'author', User::class)->sortable(),

                DateTime::make(__('Created At'), 'created_at')->sortable(),

                DateTime::make(__('Updated At'), 'updated_at')->sortable(),

                // Text::make('API', function () {
                //     return '<a href="' . route('api.ec.track.json', ['id' => $this->id]) . '" target="_blank">[x]</a>';
                // })->asHtml(),
                Text::make('API', function () {
                    return '<a href="/api/ec/track/'.$this->id.'" target="_blank">[x]</a>';
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

                Text::make('Activities', function () {
                    if ($this->taxonomyActivities()->count() > 0) {
                        return implode('<br/>', $this->taxonomyActivities()->pluck('name')->toArray());
                    }

                    return 'No activities';
                })->asHtml(),

                Text::make('Themes', function () {
                    if ($this->taxonomyThemes()->count() > 0) {
                        return implode('<br/>', $this->taxonomyThemes()->pluck('name')->toArray());
                    }

                    return 'No themes';
                })->asHtml(),

                Number::make('osmid'),

                // Text::make('Wheres', function () {
                //     if ($this->taxonomyWheres()->count() > 0) {
                //         return implode('<br/>', $this->taxonomyWheres()->pluck('name')->toArray());
                //     }
                //     return 'No wheres';
                // })->asHtml(),

                // Text::make('API', function () {
                //     return '<a href="/api/ec/track/'.$this->id.'" target="_blank">[x]</a>';
                // })->asHtml(),
            ];
        }
    }

    public function fieldsForDetail(Request $request)
    {
        return [
            (new Tabs("EC Track Details: {$this->name} ({$this->id})", [
                'Main' => [
                    Text::make('Geohub ID', function () {
                        return $this->id;
                    })
                        ->help(__('Unique identifier for the track in Geohub.')),
                    Text::make('Author', function () {
                        return $this->user->name;
                    })
                        ->help(__('The user who created the track, affecting the content publication for associated apps.')),
                    DateTime::make('Created At')
                        ->help(__('The date and time when the track was created.')),
                    DateTime::make('Updated At')
                        ->help(__('The date and time when the track was last modified.')),
                    Number::make('OSM ID', 'osmid')
                        ->help(__('The OpenStreetMap ID associated with the track. This ID cannot be modified once set, as the data will synchronize with OSM.')),
                    Heading::make(
                        <<<'HTML'
                            <ul>
                                <li><p><strong>Name</strong>: The name of the track, also known as the title.</p></li>
                                <li><p><strong>Excerpt</strong>: A brief summary or introduction for the track, displayed in lists or previews.</p></li>
                                <li><p><strong>Description</strong>: A detailed description of the track, providing comprehensive information.</p></li>
                            </ul>
                            HTML
                    )->asHtml(),
                    NovaTabTranslatable::make([
                        Text::make(__('Name'), 'name'),
                        Textarea::make(__('Excerpt'), 'excerpt'),
                        Textarea::make('Description'),
                    ])->onlyOnDetail(),
                ],
                'Media' => [
                    Text::make('Audio', function () {
                        $this->audio;
                    })
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Boolean::make('Allow print PDF for this track', 'allow_print_pdf')
                        ->help('This option works if the "General print PDF button" option is activated prom the APP configuration. For more details please contact the amministrators!'),
                    ExternalImage::make(__('Feature Image'), function () {
                        $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                        if ($url !== '' && substr($url, 0, 4) !== 'http') {
                            $url = Storage::disk('public')->url($url);
                        }

                        return $url;
                    })
                        ->withMeta(['width' => 400])
                        ->help(__('The main image representing the track, ideally in horizontal format (1440 x 500 pixels).')),
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
                    // })->asHtml()
                    Text::make('Related Url', function () {
                        $out = '';
                        $help = '<p>Related Url: List of URLs associated with the track, each with a label for display.</p>';
                        if (is_array($this->related_url) && count($this->related_url) > 0) {
                            foreach ($this->related_url as $label => $url) {
                                $out .= "<a href='{$url}' target='_blank'>{$label}</a></br>";
                            }
                        } else {
                            $out = 'No related Url';
                        }

                        return $out.$help;
                    })->asHtml(),
                ],
                'Related POIs' => [
                    Heading::make('<p>Related POIs: Points of interest associated with the track, displayed on the map and in the track details.</p> ')->asHtml(),
                    Ecpoipopup::make(__('ecPoi'))
                        ->nullable()
                        ->onlyOnDetail()
                        ->feature($geojson ?? []),
                ],
                'Info' => [
                    Text::make('Ref')
                        ->help('The reference number or code for the track, typically found on signs.'),
                    Text::make('From')
                        ->help('The starting point of the track, visible if the related option is enabled in the app configuration.'),
                    Text::make('To')
                        ->help('The endpoint of the track, visible if the related option is enabled in the app configuration.'),
                    Boolean::make('Not Accessible')
                        ->help('Enable this option to indicate that the track is not accessible. The reason can be specified below.'),
                    Heading::make('<p>Not Accessible Message: The reason why the track is not accessible, displayed when the "Not Accessible" option is enabled.</p>')->asHtml(),
                    NovaTabTranslatable::make([
                        Textarea::make(__('Not Accessible Message'), 'not_accessible_message')->alwaysShow(),
                    ]),
                    Text::make('Distance', 'distance')
                        ->resolveUsing(function ($value, $model) {
                            return $this->generateFieldTable($model, $value, 'distance');
                        })
                        ->asHtml()
                        ->help('The length of the track, displayed in the track details.'),
                    Text::make('Duration Forward', 'duration_forward')
                        ->resolveUsing(function ($value, $model) {
                            return $this->generateFieldTable($model, $value, 'duration_forward');
                        })
                        ->asHtml()
                        ->help('The estimated time to complete the track from the start to the endpoint. Displayed in hours.'),
                    Text::make('Duration Backward', 'duration_backward')
                        ->resolveUsing(function ($value, $model) {
                            return $this->generateFieldTable($model, $value, 'duration_backward');
                        })
                        ->asHtml()
                        ->help('The estimated time to complete the track from the endpoint to the start. Displayed in hours.'),
                    Text::make('Ascent', 'ascent')
                        ->resolveUsing(function ($value, $model) {
                            return $this->generateFieldTable($model, $value, 'ascent');
                        })
                        ->asHtml()
                        ->help('The total elevation gain for the track, displayed in the track details.'),
                    Text::make('Descent', 'descent')
                        ->resolveUsing(function ($value, $model) {
                            return $this->generateFieldTable($model, $value, 'descent');
                        })
                        ->asHtml()
                        ->help('The total elevation loss for the track, displayed in the track details.'),
                    Text::make('Elevation (From)', 'ele_from')
                        ->resolveUsing(function ($value, $model) {
                            return $this->generateFieldTable($model, $value, 'ele_from');
                        })
                        ->asHtml()
                        ->help('The starting elevation of the track, displayed in the track details.'),
                    Text::make('Elevation (To)', 'ele_to')
                        ->resolveUsing(function ($value, $model) {
                            return $this->generateFieldTable($model, $value, 'ele_to');
                        })
                        ->asHtml()
                        ->help('The ending elevation of the track, displayed in the track details.'),
                    Text::make('Elevation (Min)', 'ele_min')
                        ->resolveUsing(function ($value, $model) {
                            return $this->generateFieldTable($model, $value, 'ele_min');
                        })
                        ->asHtml()
                        ->help('The minimum elevation reached on the track, displayed in the track details.'),
                    Text::make('Elevation (Max)', 'ele_max')
                        ->resolveUsing(function ($value, $model) {
                            return $this->generateFieldTable($model, $value, 'ele_max');
                        })
                        ->asHtml()
                        ->help('The maximum elevation reached on the track, displayed in the track details.'),
                ],
                'Scale' => [
                    Text::make('Difficulty')
                        ->help('Custom text for the track difficulty, overriding the "Cai Scale" if set.'),
                    Text::make('Cai Scale')
                        ->help('The official CAI difficulty scale for the track, differentiating the required effort for hiking.'),
                    NovaTabTranslatable::make([
                        Text::make('Difficulty I18n'),
                    ])
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                ],
                'Taxonomies' => [
                    Text::make('Activities', function () {
                        if ($this->taxonomyActivities()->count() > 0) {
                            return implode(',', $this->taxonomyActivities()->pluck('name')->toArray());
                        }

                        return 'No activities';
                    })
                        ->help('The taxonomy activities associated with the track.'),
                    Text::make('Wheres', function () {
                        if ($this->taxonomyWheres()->count() > 0) {
                            return implode(',', $this->taxonomyWheres()->pluck('name')->toArray());
                        }

                        return 'No Wheres';
                    })
                        ->help('The taxonomy locations associated with the track.'),
                    Text::make('First where to show', function () {
                        if ($this->taxonomy_wheres_show_first) {
                            return TaxonomyWhere::find($this->taxonomy_wheres_show_first)->name;
                        }

                        return 'Nothing selected';
                    })
                        ->help('The first "Where" taxonomy to display in the track preview list.'),
                    Text::make('Themes', function () {
                        if ($this->taxonomyThemes()->count() > 0) {
                            return implode(',', $this->taxonomyThemes()->pluck('name')->toArray());
                        }

                        return 'No Themes';
                    })
                        ->help('The taxonomy themes associated with the track.'),
                    Text::make('Targets', function () {
                        if ($this->taxonomyTargets()->count() > 0) {
                            return implode(',', $this->taxonomyTargets()->pluck('name')->toArray());
                        }

                        return 'No Targets';
                    })
                        ->help('The taxonomy targets associated with the track.'),
                    Text::make('Whens', function () {
                        if ($this->taxonomyWhens()->count() > 0) {
                            return implode(',', $this->taxonomyWhens()->pluck('name')->toArray());
                        }

                        return 'No Whens';
                    })
                        ->help('The taxonomy periods associated with the track.'),
                ],
                'Out Source' => [
                    Text::make('Out Source Feature ID', function () {
                        if (! is_null($this->out_source_feature_id)) {
                            return $this->out_source_feature_id;
                        } else {
                            return 'No Out Source Feature associated';
                        }
                    })
                        ->onlyOnDetail()
                        ->help('The identifier for the associated external source feature.'),
                    Text::make('External Source ID', function () {
                        if (! is_null($this->out_source_feature_id)) {
                            $t = $this->outSourceTrack;

                            return $t->source_id;
                        } else {
                            return 'No External Source associated';
                        }
                    })
                        ->onlyOnDetail()
                        ->help('The identifier for the associated external source.'),
                    Text::make('Endpoint', function () {
                        if (! is_null($this->out_source_feature_id)) {
                            $t = $this->outSourceTrack;

                            return $t->endpoint;
                        } else {
                            return 'No endpoint associated';
                        }
                    })
                        ->onlyOnDetail()
                        ->help('The endpoint associated with the external source feature.'),
                    Text::make('Endpoint slug', 'endpoint_slug', function () {
                        if (! is_null($this->out_source_feature_id)) {
                            $t = $this->outSourceTrack;

                            return $t->endpoint_slug;
                        } else {
                            return 'No endpoint slug associated';
                        }
                    })
                        ->onlyOnDetail()
                        ->help('The slug for the endpoint associated with the external source feature.'),
                    Text::make('Public Page', function () {
                        $help = '<p>Public Page: Link to the public page of the external source feature.</p>';
                        if (! is_null($this->out_source_feature_id)) {
                            $t = $this->outSourceTrack;
                            $url_base_api = request()->root().'/osf/'.$t->endpoint_slug.'/'.$t->source_id;

                            return "<a target='_blank' style='color:#3aadcc;' href='{$url_base_api}'>View Public Page</a>".$help;
                        } else {
                            return 'No Out Source Feature.'.$help;
                        }
                    })
                        ->asHtml(),
                    Text::make('Base API', function () {
                        $help = '<p>Link to the base API of the external source feature.</p>';
                        if (! is_null($this->out_source_feature_id)) {
                            $t = $this->outSourceTrack;
                            $url_base_api = request()->root().'/api/osf/track/'.$t->endpoint_slug.'/'.$t->source_id;

                            return "<a target='_blank' style='color:#3aadcc;' href='{$url_base_api}'>View Base Api</a>".$help;
                        } else {
                            return 'No Out Source Feature.'.$help;
                        }
                    })
                        ->asHtml(),
                        Text::make('Widget: Simple', function () {
                            $help = '<p>Simple: Link to the simple widget of the external source feature.</p>';
                            if (! is_null($this->out_source_feature_id)) {
                                $t = $this->outSourceTrack; // Retrieve the outsource track data
                        
                                // Supported languages
                                $languages = ['it' => 'IT', 'en' => 'EN', 'fr' => 'FR', 'de' => 'DE', 'es' => 'ES', 'nl' => 'NL', 'sq' => 'SQ'];
                        
                                // Generate links for each language
                                $links = "";
                                foreach ($languages as $code => $label) {
                                    $url = request()->root()."/w/osf/simple/{$t->endpoint_slug}/{$t->source_id}?lang={$code}";
                                    $links .= "<a target='_blank' style='color:#3aadcc;' href='{$url}'>View Widget Simple ({$label})</a><br>";
                                }
                        
                                return $links . $help;
                            } else {
                                return 'No Out Source Feature.' . $help;
                            }
                        })->asHtml(),
                ],
                'API' => [
                    Text::make('Public Page', function () {
                        $help = '<p>Link to the public page of this track.</p>';
                        $url_pubblic = request()->root().'/track/'.$this->id;

                        return "<a target='_blank' style='color:#3aadcc;' href='{$url_pubblic}'>View Public Page</a>".$help;
                    })->asHtml(),
                    Text::make('Base API', function () {
                        $help = '<p>Link to the base API of this track.</p>';
                        $url_base_api = request()->root().'/api/ec/track/'.$this->id;

                        return "<a target='_blank' style='color:#3aadcc;' href='{$url_base_api}'>View Base API</a>".$help;
                    })->asHtml(),
                    Text::make('Widgetz: Simple', function () {
                        $help = '<p>Link to the simple widget for this track.</p>';
                    
                        // Supported languages
                        $languages = ['it' => 'IT', 'en' => 'EN', 'fr' => 'FR', 'de' => 'DE', 'es' => 'ES', 'nl' => 'NL', 'sq' => 'SQ'];
                    
                        // Generate links for each language
                        $links = "";
                        foreach ($languages as $code => $label) {
                            $url = request()->root()."/w/simple/{$this->id}?lang={$code}";
                            $links .= "<a target='_blank' style='color:#3aadcc;' href='{$url}'>View Widget Simple ({$label})</a><br>";
                        }
                    
                        return $links . $help;
                    })->asHtml(),
                    // show a link to the track-pdf.blade.php
                    Text::make('PDF')
                        ->resolveUsing(function ($value, $resource, $attribute) {
                            $help = '<p>Link to generate a PDF for this track.</p>';

                            return '<a target="_blank" style="color:#3aadcc;" href="'.route('track.pdf', ['id' => $resource->id]).'">Generate PDF</a>'.$help;
                        })
                        ->asHtml()
                        ->onlyOnDetail(),
                ],
                'Data' => [
                    Heading::make($this->getData())->asHtml(),
                ],
                'Style' => [
                    Swatches::make('Color', 'color')
                        ->colors('text-advanced')
                        ->withProps([
                            'show-fallback' => true,
                            'fallback-type' => 'input',
                        ])
                        ->help('Select a color to associate with this track'),
                ],
            ]))->withToolbar(),
            new Panel('Map', [
                MapMultiLinestringNova3::make(__('Map'), 'geometry')->withMeta([
                    'center' => ['51', '4'],
                    'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                    'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                    'minZoom' => 7,
                    'maxZoom' => 16,
                ]),
            ]),
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

        $tab_title = 'New EC Track';
        $osmid = $this->model()->osmid;
        $isOsmidSet = ! is_null($osmid);

        if (NovaCurrentResourceActionHelper::isUpdate($request)) {
            $tab_title = "EC Track Edit: {$this->name} ({$this->id})";
        }

        return [
            Tabs::make($tab_title, [
                'Main' => [
                    Heading::make(
                        <<<'HTML'
                        <ul>
                            <li><p><strong>Name</strong>: Enter the name of the track. This will be the main title displayed.</p></li>
                            <li><p><strong>Excerpt</strong>: Provide a brief summary or introduction. This will be shown in lists or previews.</p></li>
                            <li><p><strong>Description</strong>: Add a detailed description. This field is for the full content that users will see.</p></li>
                        </ul>
                        HTML
                    )->asHtml()->onlyOnForms(),
                    NovaTabTranslatable::make([
                        Text::make(__('Name'), 'name')
                            ->readonly($isOsmidSet)
                            ->help(__($isOsmidSet ? 'This field is not editable because the OSM ID is already set.' : 'Displayed name of the POI.')),
                        Textarea::make(__('Excerpt'), 'excerpt')
                            ->help(__('Provide a brief summary or excerpt for the track. This should be a concise description.')),
                        NovaWyswyg::make('Description', 'description')
                            ->help(__('Enter a detailed description of the track. Use this field to provide comprehensive information.')),
                    ])
                        ->onlyOnForms(),
                    BelongsTo::make('Author', 'author', User::class)
                        ->searchable()
                        ->nullable()
                        ->canSee(function ($request) {
                            return $request->user()->can('Admin', $this);
                        })->help(__('Author who created the track: this affects the publication of the content, which will be available for apps associated with the indicated author')),
                    Number::make('OSM ID', 'osmid')
                        ->help(__('OpenStreetMap ID associated with the track: once applied, it is not possible to modify data here in GeoHub as they will be synchronized with OSM')),
                ],
                'Media' => [

                    // File::make(__('Audio'), 'audio')->store(function (Request $request, $model) {
                    //     $file = $request->file('audio');

                    //     return $model->uploadAudio($file);
                    // })->acceptedTypes('audio/*')->onlyOnForms(),

                    FeatureImagePopup::make(__('Feature Image'), 'featureImage')
                        ->onlyOnForms()
                        ->feature($geojson ?? [])
                        ->apiBaseUrl('/api/ec/track/')
                        ->help(__('The feature image is displayed in the list of tracks. It is advisable to use a high-quality image in horizontal format 1440 x 500 pixels in jpg or png format. Alternatively, GeoHub will automatically create thumbnails and crop the image to fit. If you have uploaded georeferenced images in the Ec Medias section that match the track\'s location, you will find them by clicking on "Select image" in the first tab "Associated images". Otherwise, you can upload an image directly via the "Upload media" tab.')),
                    EcMediaPopup::make(__('Gallery'), 'ecMedia')
                        ->onlyOnForms()
                        ->feature($geojson ?? [])
                        ->apiBaseUrl('/api/ec/track/')
                        ->help(__('The Gallery can include multiple images of different sizes in jpg or png format. For better visualization, it is advisable to use the same size for all images in the gallery. If you have uploaded georeferenced images in the Ec Medias section that match the track\'s location, you will find them by clicking on "Select image" in the first tab "Associated images". Otherwise, you can upload an image directly via the "Upload media" tab.')),
                    Boolean::make('Allow print PDF for this track', 'allow_print_pdf')
                        ->help(__('Enable the option to generate a PDF via the "Print PDF" button, visible in the track detail view.')),
                    KeyValue::make('Related Url')
                        ->keyLabel('Label')
                        ->valueLabel('Url with https://')
                        ->actionText('Add new related url')
                        ->rules('json')
                        ->help(__('Here you can enter URLs to be displayed in the track detail view. In the label, you can enter the text to be displayed, e.g., "website.com". In the URL field with https, enter the full URL, e.g., "https://website.com/". It is possible to enter multiple URLs.')),
                ],
                'Related POIs' => [
                    Ecpoipopup::make(__('ecPoi'))
                        ->nullable()
                        ->onlyOnForms()
                        ->feature($geojson ?? [])
                        ->help(__('Here you can associate points of interest (POIs) with the track to be displayed along the entire route. They will be shown both on the map along with the track and in the track detail view. These must be created separately in the "Ec Pois" section. Once created, you can select the georeferenced POIs from this area that are located near the track.')),
                ],
                'Info' => [
                    Text::make('Ref')
                        ->help(__('Enter here the label to be shown as the track reference, visible only if "Show Track Ref Label" option is enabled in App -> OPTIONS.')),
                    Text::make('From')
                        ->help(__('Enter here the label to be shown for the stage start, visible only if the "Generate All Layers Edges" option is enabled in App -> LAYERS.')),
                    Text::make('To')
                        ->help(__('Enter here the label to be shown for the stage end, visible only if the "Generate All Layers Edges" option is enabled in App -> LAYERS.')),
                    Boolean::make('Not Accessible')
                        ->help(__('Enable this option if you want to display a red message in the track detail indicating that the track is not accessible. You can modify the content of the message in the box below.')),
                    NovaTabTranslatable::make([
                        Textarea::make(__('Not Accessible Message'), 'not_accessible_message')->alwaysShow(),
                    ]),
                    Text::make('Distance', 'distance')
                        ->help(__('Technical data displayed in the track detail. To modify it manually')),
                    Text::make('Duration Forward', 'duration_forward')
                        ->help(__('Technical data displayed in the track detail. To modify it manually Enter the time in minutes; it will be displayed in hours.')),
                    Text::make('Duration Backward', 'duration_backward')
                        ->help(__('Technical data displayed in the track detail. To modify it manually Enter the time in minutes; it will be displayed in hours.')),
                    Text::make('Ascent', 'ascent')
                        ->help(__('Technical data displayed in the track detail. To modify it manually')),
                    Text::make('Descent', 'descent')
                        ->help(__('Technical data displayed in the track detail. To modify it manually')),
                    Text::make('Elevation (From)', 'ele_from')
                        ->help(__('Technical data displayed in the track detail. To modify it manually')),
                    Text::make('Elevation (To)', 'ele_to')
                        ->help(__('Technical data displayed in the track detail. To modify it manually')),
                    Text::make('Elevation (Min)', 'ele_min')
                        ->help(__('Technical data displayed in the track detail. To modify it manually')),
                    Text::make('Elevation (Max)', 'ele_max')
                        ->help(__('Technical data displayed in the track detail. To modify it manually')),
                ],
                'Scale' => [
                    Text::make('Difficulty')
                        ->help(__('This field is used to customize the difficulty text in the track detail. If entered, the "Cai Scale" field will be ignored.')),
                    Select::make('Cai Scale')->options([
                        'T' => 'Turistico (T)',
                        'E' => 'Escursionistico (E)',
                        'EE' => 'Per escursionisti esperti (EE)',
                        'EEA' => 'Alpinistico (EEA)',
                    ])
                        ->help(__('Here you can associate one of the indicated CAI difficulty scales to differentiate the required effort for hiking itineraries. This can be viewed in detail via this <a href="https://archivio.cai.it/sezione/vipiteno-c-a-i-a-a/attivita/difficolta/#:~:text=EE%20per%20Escursionisti%20Esperti,o%20di%20roccia%20e%20detriti" target="_blank">link</a>.')),
                    NovaTabTranslatable::make([
                        Text::make('Difficulty I18n'),
                    ])
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    // TODO: Complete the implementation logic for the 'Difficulty I18n' field.
                    // After the logic is implemented, remove the hide methods to make this field visible.
                ],
                'Taxonomies' => [
                    Select::make('First taxonomy where to show', 'taxonomy_wheres_show_first')->options(function () {
                        return $this->taxonomyWheres->pluck('name', 'id')->toArray();
                    })->nullable()
                        ->help(__('Select the "Where" taxonomy you want to display in the track preview through the list shown by the app.')),
                    // AttachMany::make('TaxonomyWheres'),
                    AttachMany::make('TaxonomyActivities')
                        ->showPreview()
                        ->help(__('Select one or more activities taxonomies to associate with the track. Click "Preview" to display the selected ones.')),
                    AttachMany::make('TaxonomyTargets')
                        ->showPreview()
                        ->help(__('Select one or more targets taxonomies to associate with the track. Click "Preview" to display the selected ones.')),
                    // AttachMany::make('TaxonomyWhens'),
                    AttachMany::make('TaxonomyThemes')
                        ->showPreview()
                        ->help(__('Select one or more themes taxonomies to associate with the track. Click "Preview" to display the selected ones.')),
                ],
                'Style' => [
                    Swatches::make('Color', 'color')
                        ->colors('text-advanced')->withProps([
                            'show-fallback' => true,
                            'fallback-type' => 'input',
                        ])
                        ->help(__('Choose a color to associate with the individual track.')),
                ],
            ]),
            new Panel('Map', [
                MapMultiLinestringNova3::make(__('Map'), 'geometry')->withMeta([
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

    /**
     * Get the cards available for the request.
     */
    public function cards(Request $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     */
    public function filters(Request $request): array
    {
        if ($request->user()->hasRole('Editor')) {
            return [
                new SearchableFromOSMID,
                new HasFeatureImage,
                new HasImageGallery,
                new SelectFromActivities,
                new SelectFromThemesTrack,
                new SelectFromWheresTrack,
            ];
        }

        return [];
    }

    /**
     * Get the lenses available for the resource.
     */
    public function lenses(Request $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     */
    public function actions(Request $request): array
    {
        return [
            new RegenerateEcTrack,
            new UpdateEcTracksDEMAction,
            new UpdateEcTracks3DDEMAction,
            new BulkEditThemesEcResourceAction,
            new ReverseEcTrackGeomtryAction,
            new EnrichDataFromDemOsm,
            (new DownloadExcelEcTrackAction)->allFields()->except('geometry')->withHeadings(),
            (new CreateTracksWithOSMIDAction)->standalone(),
            (new UploadTrackFile)->standalone(),
        ];
    }

    protected function generateFieldTable($model, $currentValue, $field)
    {
        $demData = json_decode($model->dem_data, true);
        $osmData = json_decode($model->osm_data, true);
        $manualData = json_decode($model->manual_data, true);
        $osmid = $model->osmid;
        $demValue = $demData[$field] ?? null;
        $osmValue = $osmData[$field] ?? null;
        $manualValue = $manualData[$field] ?? null;
        // Determine the indicator
        $indicator = 'MANUAL';
        if (is_null($currentValue)) {
            $indicator = 'EMPTY';
        } elseif ($currentValue == $manualValue) {
            $indicator = 'MANUAL';
        } elseif ($osmid != null && $currentValue == $osmValue) {
            $indicator = 'OSM';
        } elseif ($currentValue == $demValue) {
            $indicator = 'DEM';
        }

        // Start table with compact styles and centered text
        $table = '<table style="border-collapse: collapse; width: auto;min-width:400px; margin: 0 auto;">';
        $table .= '<tr>';
        // if ($demValue !== null) {
        $table .= '<th style="border: 1px solid #ddd; padding: 4px; text-align: center; white-space: nowrap;">DEM</th>';
        // }
        if ($osmid !== null) {
            $table .= '<th style="border: 1px solid #ddd; padding: 4px; text-align: center; white-space: nowrap;">OSM</th>';
        }
        $table .= '<th style="border: 1px solid #ddd; padding: 4px; text-align: center; white-space: nowrap;">MANUAL</th>';
        $table .= '<th style="border: 1px solid #ddd; padding: 4px; text-align: center; white-space: nowrap;">CURRENT VALUE ('.$indicator.')</th>';
        $table .= '</tr>';

        // Data row
        $table .= '<tr>';
        //   if ($demValue !== null) {
        $table .= '<td style="border: 1px solid #ddd; padding: 4px; text-align: center; white-space: nowrap;">'.$demValue.'</td>';
        // }
        if ($osmid !== null) {
            $table .= '<td style="border: 1px solid #ddd; padding: 4px; text-align: center; white-space: nowrap;">'.$osmValue.'</td>';
        }
        $table .= '<td style="border: 1px solid #ddd; padding: 4px; text-align: center; white-space: nowrap;">'.$manualValue.'</td>';
        $table .= '<td style="border: 1px solid #ddd; padding: 4px; text-align: center; white-space: nowrap;">'.$currentValue.'</td>';
        $table .= '</tr>';

        // End table
        $table .= '</table>';

        return $table;
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

        <tr><td><i>main</i></td><td>id</td><td>int8</td><td>NO</td><td>AUTO</td><td>-</td><td>NO</td><td>Geohub ID</td><td>TRACK identification code in the Geohub</td></tr>
        <tr><td><i>main</i></td><td>user_id</td><td>int4</td><td>NO</td><td>NULL</td><td>users</td><td>NO</td><td>Author</td><td>TRACK author: foreign key wiht table users</td></tr>
        <tr><td><i>main</i></td><td>created_at</td><td>timestamp(0)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Created At</td><td>When TRACK has been created: datetime</td></tr>
        <tr><td><i>main</i></td><td>updated_at</td><td>timestamp(0)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Updated At</td><td>When TRACK has been modified last time: datetime</td></tr>
        <tr><td><i>main</i></td><td>name</td><td>text</td><td>NO</td><td>NULL</td><td>-</td><td>YES</td><td>Name</td><td>Name of the TRACK, also know as title</td></tr>
        <tr><td><i>main</i></td><td>description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Description</td><td>Descrption of the TRACK</td></tr>
        <tr><td><i>main</i></td><td>excerpt</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Excerpt</td><td>Short Description of the TRACK</td></tr>
        <tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
        <tr><td><i>media</i></td><td>audio</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>NO*</td><td>Audio</td><td>Audio file associated to the TRACK: tipically is the description text2speach</td></tr>
        <tr><td><i>media</i></td><td>related_url</td><td>json</td><td>YES</td><td>NULL</td><td>-</td><td>NO*</td><td>Related Url</td><td>List (label->url) of URL associated to the TRACK</td></tr>
        <tr><td><i>media</i></td><td>feature_image</td><td>int4</td><td>YES</td><td>NULL</td><td>ec_media</td><td>NO</td><td>Feature Image</td><td>Main image representig the TRACK: foreign key with ec_media</td></tr>
        <tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
        <tr><td><i>map</i></td><td>geometry</td><td>geometry</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Map</td><td>The TRACK geometry (linestring, 3D)</td></tr>
        <tr><td><i>map</i></td><td>slope</td><td>json</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>TBD</td><td>TBD</td></tr>
        <tr><td><i>map</i></td><td>mbtiles</td><td>json</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>TBD</td><td>TBD</td></tr>
        <tr><td><i>map</i></td><td>elevation_chart_image</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>TBD</td><td>TBD</td></tr>
        <tr><td><i>map</i></td><td>related_poi</td><td>N:N</td><td>YES</td><td>NULL</td><td>ec_poi_ec_track</td><td>NO</td><td>ecPoi</td><td>Related Pois: pois along the tracks, sorted by the direction of travel</td></tr>
        <tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
        <tr><td><i>info</i></td><td>ref</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Ref</td><td>"ref" stands for "reference" and is used for reference numbers or codes. It represents, when it exists, the official number of the path associated with the TRACK, the one that is usually found on the ground in the horizontal and vertical signs</td></tr>
        <tr><td><i>info</i></td><td>from</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>NO*</td><td>From</td><td>TRACK's starting position: name of the town or similar</td></tr>
        <tr><td><i>info</i></td><td>to</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO*</td><td>To</td><td>TRACK's ending position: name of the town or similar</td></tr>
        <tr><td><i>info</i></td><td>not_accessible</td><td>bool</td><td>NO</td><td>FALSE</td><td>-</td><td>NO</td><td>Not Accessible</td><td>TRUE when the track is NOT accessible for some reason</td></tr>
        <tr><td><i>info</i></td><td>not_accessible_message</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>NO*</td><td>Not Accessible Message</td><td>Reason why TRACK is not accessible, used only whe field "not_accessible" is true</td></tr>
        <tr><td><i>info</i></td><td>distance</td><td>float8</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Distance</td><td>TRACK's lenght in kilometer</td></tr>
        <tr><td><i>info</i></td><td>duration_forward</td><td>int4</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Duration Forward</td><td>Estimation of the duration of the TRACK when it is traveled from the "from" point to the "to" point (minutes)</td></tr>
        <tr><td><i>info</i></td><td>duration_backward</td><td>int4</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Duration Backward</td><td>Estimation of the duration of the TRACK when it is traveled from the "to" point to the "from" point (minutes)</td></tr>
        <tr><td><i>info</i></td><td>ascent</td><td>float8</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Ascent</td><td>Positive elevation gain (meter)</td></tr>
        <tr><td><i>info</i></td><td>descent</td><td>float8</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Descent</td><td>Negative elevation gain (meter)</td></tr>
        <tr><td><i>info</i></td><td>ele_from</td><td>float8</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Elevation (from)</td><td>Elevation at the starting point (meter)</td></tr>
        <tr><td><i>info</i></td><td>ele_to</td><td>float8</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Elevation (to)</td><td>Elevation at the ending point (meter)</td></tr>
        <tr><td><i>info</i></td><td>ele_min</td><td>float8</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Elevation Min</td><td>Mininum elevation of the TRACK</td></tr>
        <tr><td><i>info</i></td><td>ele_max</td><td>float8</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Elevation Max</td><td>Maximum elevation of the TRACK</td></tr>
        <tr><td><i>info</i></td><td>distance_comp</td><td>float8</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>TBD</td><td></td></tr>
        <tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
        <tr><td><i>scale</i></td><td>difficulty</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO*</td><td>Difficulty</td><td>Difficulty free (short) description</td></tr>
        <tr><td><i>scale</i></td><td>cai_scale</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>CAI Scale</td><td>Hiking difficulty (T,E,EE,EEA)</td></tr>
        <tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
        <tr><td><i>outsource</i></td><td>source_id</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>TBD</td><td></td></tr>
        <tr><td><i>outsource</i></td><td>import_method</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>TBD</td><td></td></tr>
        <tr><td><i>outsource</i></td><td>source</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>TBD</td><td></td></tr>
        <tr><td><i>outsource</i></td><td>out_source_feature_id</td><td>int8</td><td>YES</td><td>NULL</td><td>out_source_features</td><td>NO</td><td>Out Source Feature</td><td>Out Source connected to the TRACK</td></tr>

        </table>
        HTML;

        return $text;
    }
}
