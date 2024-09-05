<?php

namespace App\Nova;

use App\Helpers\NovaCurrentResourceActionHelper;
use App\Nova\Actions\RegenerateEcMedia;
use Chaseconey\ExternalImage\ExternalImage;
use Eminiarts\Tabs\Tabs;
use Eminiarts\Tabs\TabsOnEdit;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Khalin\Nova\Field\Link;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Panel;
use NovaAttachMany\AttachMany;
use Laravel\Nova\Http\Requests\NovaRequest;
use Titasgailius\SearchRelations\SearchesRelations;
use DigitalCreative\MegaFilter\HasMegaFilterTrait;
use Laravel\Nova\Fields\Heading;
use Ncus\InlineIndex\InlineIndex;
use Wm\MapPointNova3\MapPointNova3;
use Laravel\Nova\Fields\BelongsToMany;

class EcMedia extends Resource
{
    use TabsOnEdit;
    use SearchesRelations;
    use HasMegaFilterTrait;


    use TabsOnEdit;
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\EcMedia::class;
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
     * @param Request $request
     *
     * @return array
     */
    public function fields(Request $request): array
    {
        if (NovaCurrentResourceActionHelper::isIndex($request)) {
            return $this->index();
        }

        if (NovaCurrentResourceActionHelper::isDetail($request)) {
            return $this->detail();
        }

        if (NovaCurrentResourceActionHelper::isForm($request)) {
            return $this->form($request);
        }


        $fields = [
            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name')->sortable(),
                Textarea::make(__('Description'), 'description')->rows(3)->hideFromIndex(),
            ]),
            MorphToMany::make('TaxonomyWheres'),
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideWhenCreating(),
            Text::make(__('Excerpt'), 'excerpt')->onlyOnDetail(),
            Text::make(__('Source'), 'source')->onlyOnDetail(),
            Image::make('Url')->onlyOnForms()->hideWhenUpdating(),
            ExternalImage::make('Image', function () {
                $url = $this->model()->url;
                if (substr($url, 0, 4) !== 'http') {
                    $url = Storage::disk('public')->url($url);
                }

                return $url;
            })->withMeta(['width' => 500]),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            DateTime::make(__('Updated At'), 'updated_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            MapPointNova3::make(__('Map'), 'geometry')->withMeta([
                'center' => ["51", "4"],
                'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                'minZoom' => 7,
                'maxZoom' => 16,
            ])->onlyOnDetail(),

            Link::make('geojson', 'id')->hideWhenUpdating()->hideWhenCreating()
                ->url(function () {
                    return isset($this->id) ? route('api.ec.media.geojson', ['id' => $this->id]) : '';
                })
                ->text(__('Open GeoJson'))
                ->icon()
                ->blank(),
        ];

        if (isset($this->model()->thumbnails)) {
            $fields[] = Panel::make("Thumbnails", $this->_getThumbnailsFields());
        }

        return $fields;
    }


    private function index()
    {
        return [
            ExternalImage::make('Image', function () {
                $thumbnails = $this->model()->thumbnails;
                $url = '';
                if ($thumbnails) {
                    $thumbnails = json_decode($thumbnails, true);
                    if ($thumbnails[array_key_first($thumbnails)]) {
                        $url = $thumbnails[array_key_first($thumbnails)];
                    }
                }
                if (!$url) {
                    $url = $this->model()->url;
                    if (substr($url, 0, 4) !== 'http') {
                        $url = Storage::disk('public')->url($url);
                    }
                }

                return $url;
            }),

            Text::make('Name')->sortable(),
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideFromIndex(),
            Date::make(__('Created At'), 'created_at')->sortable()->hideFromIndex(),
            Date::make(__('Updated At'), 'updated_at')->sortable()->hideFromIndex(),
            Text::make('Url', function () {
                $url = $this->model()->url;
                if (substr($url, 0, 4) !== 'http') {
                    $url = Storage::disk('public')->url($url);
                }

                return '<a href="' . $url . '" target="_blank">' . __('Original image') . '</a>';
            })->asHtml(),
            InlineIndex::make('Rank')
                ->sortable()
                ->canSee(function ($request) {
                    return $request->viaResource() === 'App\Nova\EcPoi' || $request->viaResource() === 'App\Nova\EcTrack';
                }),
            Link::make('GeoJSON', 'id')
                ->url(function () {
                    return isset($this->id) ? route('api.ec.media.geojson', ['id' => $this->id]) : '';
                })
                ->text(__('Open GeoJson'))
                ->icon()
                ->blank(),
        ];
    }

    public function fieldsForDetail(Request $request): array
    {
        return [
            (new Tabs(
                "Taxonomy Where Details: {$this->name} ($this->id)",
                [
                    'Main' => [
                        Text::make('Geohub ID', function () {
                            return $this->id;
                        })->help('Unique identifier for the media content in Geohub.'),
                        BelongsTo::make('Author', 'author', User::class)
                            ->help(__('The author of the media content, associated via foreign key with the users table.')),
                        DateTime::make(__('Created At'), 'created_at')
                            ->help(__('The date and time when the media content was created.')),
                        DateTime::make(__('Updated At'), 'updated_at')
                            ->help(__('The last date and time when the media content was modified.')),
                        NovaTabTranslatable::make([
                            Text::make(__('Name'), 'name')
                                ->help(__('The name of the media content, also known as the title.')),
                            Textarea::make(__('Excerpt'), 'excerpt')
                                ->hideFromIndex()
                                ->hideFromDetail()
                                ->hideWhenCreating()
                                ->hideWhenUpdating(),
                            Textarea::make(__('Description'), 'description')
                                ->hideFromIndex()
                                ->hideFromDetail()
                                ->hideWhenCreating()
                                ->hideWhenUpdating(),
                        ]),
                    ],
                    'Images' => $this->getImages(),
                    'Map' => [
                        Heading::make('
                            <p>Map: The geometry of the media content (geographical point).</p>
                        ')->asHtml(),
                        MapPointNova3::make(__('Map'), 'geometry')->withMeta([
                            'center' => ["51", "4"],
                            'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                            'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                            'minZoom' => 7,
                            'maxZoom' => 16,
                        ]),
                    ],
                    'Taxonomies' => [
                        Text::make('Activities', function () {
                            if ($this->taxonomyActivities()->count() > 0) {
                                return implode(',', $this->taxonomyActivities()->pluck('name')->toArray());
                            }
                            return 'No activities';
                        })->help(__('The taxonomy activities associated with the media content.')),
                        Text::make('Wheres', function () {
                            if ($this->taxonomyWheres()->count() > 0) {
                                return implode(',', $this->taxonomyWheres()->pluck('name')->toArray());
                            }
                            return 'No Wheres';
                        })->help(__('The taxonomy locations associated with the media content.')),
                        Text::make('Themes', function () {
                            if ($this->taxonomyThemes()->count() > 0) {
                                return implode(',', $this->taxonomyThemes()->pluck('name')->toArray());
                            }
                            return 'No Themes';
                        })->help(__('The taxonomy themes associated with the media content.')),
                        Text::make('Targets', function () {
                            if ($this->taxonomyTargets()->count() > 0) {
                                return implode(',', $this->taxonomyTargets()->pluck('name')->toArray());
                            }
                            return 'No Targets';
                        })->help(__('The taxonomy targets associated with the media content.')),
                        Text::make('Whens', function () {
                            if ($this->taxonomyWhens()->count() > 0) {
                                return implode(',', $this->taxonomyWhens()->pluck('name')->toArray());
                            }
                            return 'No Whens';
                        })->help(__('The taxonomy periods associated with the media content.')),
                    ],
                    'Data' => [
                        Heading::make($this->getData())->asHtml(),
                    ],

                ]
            ))->withToolbar(),
            BelongsToMany::make('Tracks', 'ecTracks', 'App\Nova\EcTrack')->searchable()->nullable(),
            BelongsToMany::make('Pois', 'ecPois', 'App\Nova\EcPoi')->searchable()->nullable(),
        ];
    }


    private function form($request)
    {

        try {
            $geojson = $this->model()->getGeojson();
        } catch (Exception $e) {
            $geojson = null;
        }


        $tab_title = "New EC Media";
        if (NovaCurrentResourceActionHelper::isUpdate($request)) {
            $tab_title = "EC Media Edit: {$this->name} ({$this->id})";
        }

        return [
            (new Tabs(
                $tab_title,
                [
                    'Main' => [
                        Heading::make(
                            <<<HTML
                            <ul>
                                <li><p><strong>Name</strong>: Enter the name of the item. This will be the main title displayed.</p></li>
                                <li><p><strong>Excerpt</strong>: Provide a brief summary or introduction. This will be shown in lists or previews.</p></li>
                                <li><p><strong>Description</strong>: Add a detailed description. This field is for the full content that users will see.</p></li>
                            </ul>
                            HTML
                        )->asHtml()->onlyOnForms(),
                        NovaTabTranslatable::make([
                            Text::make(__('Name'), 'name'),
                            Textarea::make(__('Excerpt'), 'excerpt'),
                            Textarea::make(__('Description'), 'description'),
                        ]),
                        BelongsTo::make('Author', 'author', User::class)
                            ->searchable()
                            ->nullable()
                            ->canSee(function ($request) {
                                return $request->user()->can('Admin');
                            })
                            ->help(__("Associate the author of the app that will show the media")),
                    ],
                    'Images' => [
                        Image::make('Url')
                            ->help(__('Choose the image to upload')),
                    ],
                    'Map' => [],
                    'Taxonomies' => [
                        AttachMany::make('TaxonomyActivities')
                            ->showPreview()
                            ->help(__('Select one or more activities taxonomies to associate with the media. Click "Preview" to display the selected ones.')),
                        AttachMany::make('TaxonomyTargets')
                            ->showPreview()
                            ->help(__('Select one or more targets taxonomies to associate with the media. Click "Preview" to display the selected ones.')),
                        AttachMany::make('TaxonomyThemes')
                            ->showPreview()
                            ->help(__('Select one or more themes taxonomies to associate with the media. Click "Preview" to display the selected ones.')),
                    ],


                ]
            ))->withToolbar(),
            new Panel('Map / Geographical info', [
                MapPointNova3::make(__('Map'), 'geometry')->withMeta([
                    'center' => ["51", "4"],
                    'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                    'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                    'minZoom' => 7,
                    'maxZoom' => 16,
                ])
            ]),

        ];
    }


    private function getImages()
    {
        $return = [];
        $return[] = Text::make('Original Image', function () {
            $url = $this->model()->url;
            if (substr($url, 0, 4) !== 'http') {
                $url = Storage::disk('public')->url($url);
            }

            return '<a href="' . $url . '" target="_blank">' . $url . '</a>';
        })->asHtml();

        $return[] = ExternalImage::make('Image', function () {
            $url = $this->model()->url;
            if (substr($url, 0, 4) !== 'http') {
                $url = Storage::disk('public')->url($url);
            }

            return $url;
        })->withMeta(['width' => 500]);

        if (isset($this->model()->thumbnails)) {
            $thumbnails = json_decode($this->model()->thumbnails, true);
            $fields = [];

            if (isset($thumbnails)) {
                foreach ($thumbnails as $size => $url) {
                    $return[] = Text::make($size, function () use ($url) {
                        return '<a href="' . $url . '" target="_blank">' . $url . '</a>';
                    })->asHtml();
                }
            }
        }

        return $return;
    }

    /**
     * Create the thumbnails fields to show in the EcMedia details
     *
     * @return array with the thumbnails fields
     */
    private function _getThumbnailsFields(): array
    {
        $model = $this->model();
        $thumbnails = json_decode($model->thumbnails, true);
        $fields = [];

        if (isset($thumbnails)) {
            foreach ($thumbnails as $size => $url) {
                $fields[] = ExternalImage::make($size, function () use ($url) {
                    return $url;
                })->onlyOnDetail();
            }
        }

        return $fields;
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
        if ($request->user()->hasRole('Editor')) {
            return [
                new Lenses\MyEcMediasLens(),
            ];
        }
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
            new RegenerateEcMedia(),
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
<tr><td><i>main</i></td><td>id</td><td>int8</td><td>NO</td><td>AUTO</td><td>-</td><td>NO</td><td>Geohub ID</td><td>MEDIA identification code in the Geohub</td></tr>
<tr><td><i>main</i></td><td>user_id</td><td>int4</td><td>NO</td><td>NULL</td><td>users</td><td>NO</td><td>Author</td><td>MEDIA author: foreign key wiht table users</td></tr>
<tr><td><i>main</i></td><td>created_at</td><td>timestamp(0)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Created At</td><td>When MEDIA has been created: datetime</td></tr>
<tr><td><i>main</i></td><td>updated_at</td><td>timestamp(0)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Updated At</td><td>When MEDIA has been modified last time: datetime</td></tr>
<tr><td><i>main</i></td><td>name</td><td>text</td><td>NO</td><td>NULL</td><td>-</td><td>YES</td><td>Name</td><td>Name of the MEDIA, also know as title</td></tr>
<tr><td><i>main</i></td><td>description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Description</td><td>Descrption of the MEDIA</td></tr>
<tr><td><i>main</i></td><td>excerpt</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Excerpt</td><td>Short Description of the MEDIA</td></tr>
<tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>images</i></td><td>url</td><td>varchar(255)</td><td>NO</td><td>NULL</td><td>NULL</td><td>NO</td><td>Original Image</td><td>URL of the original image first updated</td></tr>
<tr><td><i>images</i></td><td>thumbnails</td><td>json</td><td>YES</td><td>NULL</td><td>NULL</td><td>NO</td><td>Thubnails</td><td>List of all thumbnails generated by the GEOMIXER task</td></tr>
<tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>map</i></td><td>geometry</td><td>geography</td><td>YES</td><td>NULL</td><td>NULL</td><td>NO</td><td>Map</td><td>The MEDIA geometry (point)</td></tr>
<tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>outsource</i></td><td>source_id</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>NULL</td><td>NO</td><td>TBD</td><td></td></tr>
<tr><td><i>outsource</i></td><td>import_method</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>NULL</td><td>NO</td><td>TBD</td><td></td></tr>
<tr><td><i>outsource</i></td><td>source</td><td>text</td><td>YES</td><td>NULL</td><td>NULL</td><td>NO</td><td>TBD</td><td></td></tr>
</table>
HTML;
        return $text;
    }
}
