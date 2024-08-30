<?php

namespace App\Nova;

use App\Models\User;
use Eminiarts\Tabs\Tabs;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Eminiarts\Tabs\TabsOnEdit;
use NovaAttachMany\AttachMany;
use Yna\NovaSwatches\Swatches;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\Textarea;
use Ncus\InlineIndex\InlineIndex;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Chaseconey\ExternalImage\ExternalImage;
use Laravel\Nova\Http\Requests\NovaRequest;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;

class Layer extends Resource
{
    use TabsOnEdit;

    public static function indexQuery(NovaRequest $request, $query)
    {
        if ($request->user()->can('Admin')) {
            return $query;
        }
        $userId = $request->user()->id;
        $userApps = User::find($userId)->apps()->pluck('id')->toArray();
        return $query->whereIn('app_id', $userApps);
    }
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Layer::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name'; //* IMPORTANT this parameter affects also the attachmany field in OverlayLayer Nova Resource. If you change to ID, the attachmany field will show the ID instead of the name.

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'name',
        'title',
        'subtitle'
    ];



    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        // $my_url = $request->server->get('HTTP_REFERER');
        // if( strpos($my_url,'/edit') !== FALSE ) {
        //     return $this->update();
        // } else {
        //     return $this->create();
        // }
        return [
            ID::make('id'),
            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name')
            ]),
            AttachMany::make('Associated apps', 'associatedApps',  \App\Nova\App::class),
            AttachMany::make('taxonomyActivities'),
            AttachMany::make('TaxonomyThemes'),
            AttachMany::make('TaxonomyTargets'),
            AttachMany::make('TaxonomyWhens'),
            AttachMany::make('TaxonomyWheres'),
            // MorphToMany::make('TaxonomyWheres')->searchable()->nullable(),
        ];
    }

    public function fieldsForIndex(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            BelongsTo::make('App'),
            Text::make('Name')->required()->sortable(),
            // Number::make('Rank')->sortable(),
            InlineIndex::make('Rank')->sortable()->rules('required'),
            // MorphToMany::make('TaxonomyWheres')->searchable()->nullable(),


        ];
    }

    public function fieldsForDetail(Request $request)
    {
        return [
            (new Tabs("LAYER Details: {$this->name} (GeohubId: {$this->id})", [
                'MAIN' => [
                    BelongsTo::make('App'),
                    Text::make('Name')->required(),
                    NovaTabTranslatable::make([
                        Text::make('Title'),
                        Text::make('Subtitle'),
                        Textarea::make('Description')->alwaysShow(),
                        Text::make('Track Type', 'track_type'),
                    ])
                ],
                'MEDIA' => [
                    ExternalImage::make(__('Feature Image'), function () {
                        $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                        if ('' !== $url && substr($url, 0, 4) !== 'http') {
                            $url = Storage::disk('public')->url($url);
                        }

                        return $url;
                    })->withMeta(['width' => 400])->onlyOnDetail(),
                ],
                'BEHAVIOUR' => [
                    Boolean::make('Generate Edges', 'generate_edges'),
                    Boolean::make('No Details', 'noDetails')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Boolean::make('No Interaction', 'noInteraction')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make('Zoom Min', 'minZoom')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make('Zoom Max', 'maxZoom')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Boolean::make('Prevent Filter', 'preventFilter')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Boolean::make('Invert Polygons', 'invertPolygons')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Boolean::make('Alert', 'alert')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Boolean::make('Show Label', 'show_label')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                ],
                'STYLE' => [
                    Swatches::make('Color', 'color')->colors('text-advanced')->withProps([
                        'show-fallback' => true,
                        'fallback-type' => 'input',
                    ]),
                    Swatches::make('Fill Color', 'fill_color')->colors('text-advanced')->withProps([
                        'show-fallback' => true,
                        'fallback-type' => 'input',
                    ]),
                    Number::make('Fill Opacity', 'fill_opacity')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make('Stroke Width', 'stroke_width')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make('Stroke Opacity', 'stroke_opacity')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make('Zindex', 'zindex')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Text::make('Line Dash', 'line_dash')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating()
                ],
                'DATA' => [
                    Boolean::make('Use APP bounding box to limit data', 'data_use_bbox')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Boolean::make('Use features only created by myself', 'data_use_only_my_data')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Text::make('associatedApps', function () {
                        if ($this->associatedApps()->count() > 0) {
                            return implode(',', $this->associatedApps()->pluck('name')->toArray());
                        }
                        return 'No associated apps';
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

                ]

            ]))->withToolbar(),
        ];
    }
    public function fieldsForCreate(Request $request)
    {
        return [
            Text::make('Name')->required(),
            BelongsTo::make('App')->searchable()->showCreateRelationButton(),
            Text::make('Title'),
        ];
    }
    public function fieldsForUpdate(Request $request)
    {

        $title = "EDIT LAYER: {$this->name} (LAYER GeohubId: {$this->id})";
        if ($this->app) {
            $title = "EDIT LAYER: '{$this->name}' belongs to APP '{$this->app->name} '(LAYER GeohubId: {$this->id})";
        }
        $mainTab = [
            Text::make('Name')
                ->required()
                ->help(__('Name associated with the layer in GeoHub. This is not the name displayed on the app home screen; for that, refer to the "Title" field below.')),
            NovaTabTranslatable::make([
                Heading::make('
                                        <h4>Instructions for Name, Excerpt, and Description Fields</h4>
                                        <p><strong>Title:</strong> Enter the title of the layer. This will be the main title displayed.</p>
                                        <p><strong>Description:</strong> Add a detailed description. This field is for the full content that users will see.</p>
                                        <p><strong>Track Type:</strong> Text displayed as the header of the column listing the tracks in the app.</p>
                                    ')->asHtml(),
                Text::make('Title')
                    ->help(__('Title of the layer displayed on the app home screen.')),
                Text::make('Subtitle')
                    ->hideFromIndex()
                    ->hideFromDetail()
                    ->hideWhenCreating()
                    ->hideWhenUpdating(),
                Textarea::make('Description')
                    ->alwaysShow()
                    ->help(__('Description displayed in the layer details on the app home screen.')),
                Text::make('Track Type', 'track_type')
                    ->help(__('Name displayed as the header of the layer\'s track list.')),
            ]),
        ];

        $behaviourTab = [
            Boolean::make('Generate Edges', 'generate_edges')
                ->help(__('By enabling the Edge function, you will be able to view the tracks of the layer with the UI of stage itineraries. The function will be active only for the indicated layer. If you want to enable the Edge function for all layers of the app, activate it from App > Layer "Generate All Layers Edges".')),
            Boolean::make('No Details', 'noDetails')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Boolean::make('No Interaction', 'noInteraction')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Number::make('Zoom Min', 'minZoom')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Number::make('Zoom Max', 'maxZoom')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Boolean::make('Prevent Filter', 'preventFilter')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Boolean::make('Invert Polygons', 'invertPolygons')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Boolean::make('Alert', 'alert')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Boolean::make('Show Label', 'show_label')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
        ];

        $styleTab = [
            Swatches::make('Color', 'color')
                ->default('#de1b0d')
                ->colors('text-advanced')
                ->withProps([
                    'show-fallback' => true,
                    'fallback-type' => 'input',
                ])->help(__('Choose a color to associate with the Layer. All tracks associated with the layer will have same color.')),
            Swatches::make('Fill Color', 'fill_color')
                ->default('#de1b0d')
                ->colors('text-advanced')
                ->withProps([
                    'show-fallback' => true,
                    'fallback-type' => 'input',
                ])
                ->help(__('Choose a fill color to associate with the layer. All tracks associated with the layer will have same fill color.')),
            Number::make('Fill Opacity', 'fill_opacity')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Number::make('Stroke Width', 'stroke_width')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Number::make('Stroke Opacity', 'stroke_opacity')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Number::make('Zindex', 'zindex')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Text::make('Line Dash', 'line_dash')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating()
        ];

        $dataTab = [
            Boolean::make('Use APP bounding box to limit data', 'data_use_bbox')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Boolean::make('Use features only created by myself', 'data_use_only_my_data')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            AttachMany::make('Associated Apps', 'associatedApps',  \App\Nova\App::class)
                ->showPreview()
                ->help(__('It is possible to share the content of tracks from one app to another. Select the app that shares its track content in this layer. Additional taxonomy filters can be added, for example, if "Activities" and "MTB" are also selected, only the MTB tracks of the associated app will be shown. Click "Preview" to display the selected ones.')),
            AttachMany::make('taxonomyActivities')
                ->showPreview()
                ->help(__('Select one or more activities taxonomies to associate with the Layer. Click "Preview" to display the selected ones.')),
            AttachMany::make('TaxonomyThemes')
                ->showPreview()
                ->help(__('Select one or more themes taxonomies to associate with the Layer. Click "Preview" to display the selected ones.')),
            AttachMany::make('TaxonomyTargets')
                ->showPreview()
                ->help(__('Select one or more targets taxonomies to associate with the Layer. Click "Preview" to display the selected ones.')),
            AttachMany::make('TaxonomyWhens')
                ->showPreview()
                ->help(__('Select one or more whens taxonomies to associate with the Layer. Click "Preview" to display the selected ones.')),
            AttachMany::make('TaxonomyWheres')
                ->showPreview()
                ->help(__('Select one or more wheres taxonomies to associate with the Layer. Click "Preview" to display the selected ones.'))
        ];

        $mediaTab =  [
            BelongsTo::make('Feature Image', 'featureImage', 'App\Nova\EcMedia')
                ->searchable()
                ->nullable()
                ->help(__('The feature image is displayed in the list of Layers as the image in the app home screen. You can find the desired image previously uploaded in Ec Medias by searching and clicking on "click to choose".')),
        ];

        //if the logged user has role editor only show the main tab
        if ($request->user()->hasRole('Editor')) {
            return [
                NovaTabTranslatable::make([
                    Text::make('Title'),
                    Text::make('Subtitle'),
                    Textarea::make('Description')->alwaysShow(),
                    Text::make('Track Type', 'track_type'),
                ]),
                BelongsTo::make('Feature Image', 'featureImage', 'App\Nova\EcMedia')
                    ->searchable()
                    ->nullable(),
                Swatches::make('Color', 'color')->default('#de1b0d')->colors('text-advanced')->withProps([
                    'show-fallback' => true,
                    'fallback-type' => 'input',
                ]),
            ];
        }
        return [
            (new Tabs($title, [
                'MAIN' => $mainTab,
                'MEDIA' => $mediaTab,
                'BEHAVIOUR' => $behaviourTab,
                'STYLE' => $styleTab,
                'DATA' => $dataTab
            ]))->withToolbar(),
            // MorphToMany::make('TaxonomyWheres')->searchable()->nullable()
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [
            // (new NovaSearchableBelongsToFilter('App'))
            // ->fieldAttribute('app')
            // ->filterBy('app_id'),
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }

    public function authorizedTo(Request $request, $ability)
    {
        //can see only layers belonging to the app of the logged user. If the layer is not belonging to the app of the logged user, error 403 is thrown. Admin can see all layers.
        $user = $request->user();
        if ($user->hasRole('Admin')) {
            return true;
        }
        $userId = $request->user()->id;
        $userApps = User::find($userId)->apps()->pluck('id')->toArray();
        return $this->app_id && in_array($this->app_id, $userApps);
    }
}
