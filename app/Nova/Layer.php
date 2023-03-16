<?php

namespace App\Nova;

use Eminiarts\Tabs\Tabs;
use Eminiarts\Tabs\TabsOnEdit;
use Illuminate\Http\Request;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use NovaAttachMany\AttachMany;
use Yna\NovaSwatches\Swatches;
use Ncus\InlineIndex\InlineIndex;



class Layer extends Resource
{
    use TabsOnEdit;
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
    public static $title = 'id';

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
                    Textarea::make('Description')->alwaysShow()
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
                    Swatches::make('Color', 'color')->default('#de1b0d'),
                    Swatches::make('Fill Color', 'fill_color')->default('#de1b0d'),
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
            // MorphToMany::make('TaxonomyWheres')->searchable()->nullable(),
        ];
    }
    public function fieldsForCreate(Request $request)
    {
        return [
            Text::make('Name')->required(),
            BelongsTo::make('App')->searchable()->showCreateRelationButton(),
        ];
    }
    public function fieldsForUpdate(Request $request)
    {

        $title = "EDIT LAYER: {$this->name} (LAYER GeohubId: {$this->id})";
        if ($this->app) {
            $title = "EDIT LAYER: '{$this->name}' belongs to APP '{$this->app->name} '(LAYER GeohubId: {$this->id})";
        }
        return [
            (new Tabs($title, [
                'MAIN' => [
                    Text::make('Name')->required(),
                    NovaTabTranslatable::make([
                        Text::make('Title'),
                        Text::make('Subtitle'),
                        Textarea::make('Description')->alwaysShow()
                    ]),
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
                    Swatches::make('Color', 'color')->default('#de1b0d'),
                    Swatches::make('Fill Color', 'fill_color')->default('#de1b0d'),
                    Number::make('Fill Opacity', 'fill_opacity'),
                    Number::make('Stroke Width', 'stroke_width'),
                    Number::make('Stroke Opacity', 'stroke_opacity'),
                    Number::make('Zindex', 'zindex'),
                    Text::make('Line Dash', 'line_dash')
                ],
                'DATA' => [
                    Heading::make('Use this interface to define rules to assign data to this layer'),
                    Boolean::make('Use APP bounding box to limit data', 'data_use_bbox'),
                    Boolean::make('Use features only created by myself', 'data_use_only_my_data'),
                    AttachMany::make('taxonomyActivities'),
                    AttachMany::make('TaxonomyThemes'),
                    AttachMany::make('TaxonomyTargets'),
                    AttachMany::make('TaxonomyWhens'),
                ]
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
}
