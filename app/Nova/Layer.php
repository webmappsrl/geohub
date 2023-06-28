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
        'id', 'name', 'title', 'subtitle'
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
                    Text::make('Title'),
                    Text::make('Subtitle'),
                    Textarea::make('Description')->alwaysShow(),

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
                    Boolean::make('No Details', 'noDetails'),
                    Boolean::make('No Interaction', 'noInteraction'),
                    Number::make('Zoom Min', 'minZoom'),
                    Number::make('Zoom Max', 'maxZoom'),
                    Boolean::make('Prevent Filter', 'preventFilter'),
                    Boolean::make('Invert Polygons', 'invertPolygons'),
                    Boolean::make('Alert', 'alert'),
                    Boolean::make('Show Label', 'show_label'),
                ],
                'STYLE' => [
                    Swatches::make('Color', 'color')->default('#de1b0d')->colors('text-advanced')->withProps([
                        'show-fallback' => true,
                        'fallback-type' => 'input',
                    ]),
                    Swatches::make('Fill Color', 'fill_color')->default('#de1b0d')->colors('text-advanced')->withProps([
                        'show-fallback' => true,
                        'fallback-type' => 'input',
                    ]),
                    Number::make('Fill Opacity', 'fill_opacity'),
                    Number::make('Stroke Width', 'stroke_width'),
                    Number::make('Stroke Opacity', 'stroke_opacity'),
                    Number::make('Zindex', 'zindex'),
                    Text::make('Line Dash', 'line_dash')
                ],
                'DATA' => [
                    Heading::make('Here are shown rules used to assign data to this layer'),
                    Boolean::make('Use APP bounding box to limit data', 'data_use_bbox'),
                    Boolean::make('Use features only created by myself', 'data_use_only_my_data'),
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
            Text::make('Name')->required(),
            NovaTabTranslatable::make([
                Text::make('Title'),
                Text::make('Subtitle'),
                Textarea::make('Description')->alwaysShow()
            ]),
        ];

        $behaviourTab = [
            Boolean::make('No Details', 'noDetails'),
            Boolean::make('No Interaction', 'noInteraction'),
            Number::make('Zoom Min', 'minZoom'),
            Number::make('Zoom Max', 'maxZoom'),
            Boolean::make('Prevent Filter', 'preventFilter'),
            Boolean::make('Invert Polygons', 'invertPolygons'),
            Boolean::make('Alert', 'alert'),
            Boolean::make('Show Label', 'show_label'),
        ];

        $styleTab = [
            Swatches::make('Color', 'color')->default('#de1b0d')->colors('text-advanced')->withProps([
                'show-fallback' => true,
                'fallback-type' => 'input',
            ]),
            Swatches::make('Fill Color', 'fill_color')->default('#de1b0d')->colors('text-advanced')->withProps([
                'show-fallback' => true,
                'fallback-type' => 'input',
            ]),
            Number::make('Fill Opacity', 'fill_opacity'),
            Number::make('Stroke Width', 'stroke_width'),
            Number::make('Stroke Opacity', 'stroke_opacity'),
            Number::make('Zindex', 'zindex'),
            Text::make('Line Dash', 'line_dash')
        ];

        $dataTab = [
            Heading::make('Use this interface to define rules to assign data to this layer'),
            Boolean::make('Use APP bounding box to limit data', 'data_use_bbox'),
            Boolean::make('Use features only created by myself', 'data_use_only_my_data'),
            AttachMany::make('taxonomyActivities')
                ->showPreview(),
            AttachMany::make('TaxonomyThemes')
                ->showPreview(),
            AttachMany::make('TaxonomyTargets')
                ->showPreview(),
            AttachMany::make('TaxonomyWhens')
                ->showPreview(),
        ];

        $mediaTab =  [
            BelongsTo::make('Feature Image', 'featureImage', 'App\Nova\EcMedia')
                ->searchable()
                ->nullable()
        ];

        //if the logged user has role editor only show the main tab
        if ($request->user()->hasRole('Editor')) {
            return [
                NovaTabTranslatable::make([
                    Text::make('Title'),
                    Text::make('Subtitle'),
                    Textarea::make('Description')->alwaysShow(),
                ]),
                BelongsTo::make('Feature Image', 'featureImage', 'App\Nova\EcMedia')
                    ->searchable()
                    ->nullable()
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
