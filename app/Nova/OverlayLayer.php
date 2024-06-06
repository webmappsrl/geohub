<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Text;
use NovaAttachMany\AttachMany;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Http\Requests\NovaRequest;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Number;
use Nova\Multiselect\Multiselect;
use Yna\NovaSwatches\Swatches;
use Laravel\Nova\Fields\Code;

class OverlayLayer extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\OverlayLayer::class;

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
        'id', 'name'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        // $app_name = str_replace(' ','-',strtolower($this->app->name));
        $app_name = $this->app_id;
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make('name'),
            NovaTabTranslatable::make([
                Text::make(__('Label'), 'label')
            ]),
            Boolean::make('Show this overlay by default', 'default')->help('turn this option on if you want to show this overlay by default')->hideFromIndex()->hideWhenCreating(),
            BelongsTo::make('App', 'app', App::class)
                ->searchable()
                ->hideFromIndex(),
            Text::make('Geojson_url')->hideFromIndex()->rules('nullable', 'url')->displayUsing(function ($value) {
                if ($value) {
                    $output = '<a href="' . $value . '" target="_blank">' . $value . '</a>';
                } else {
                    $output = '<span>N/A</span>';
                }
                return $output;
            })->asHtml()
                ->help('Please insert the correct Geojson URL')
                ->hideWhenUpdating(function ($request) {
                    return !is_null($this->feature_collection);
                })
                ->hideWhenCreating(function ($request) {
                    return !is_null($this->feature_collection);
                }),
            File::make('File', 'feature_collection')
                ->disk('public')
                ->path('geojson/' . $app_name)
                ->acceptedTypes(['.json', '.geojson'])
                //rename the file taking the name property from the request
                ->storeAs(function (Request $request) {
                    return $request->feature_collection->getClientOriginalName();
                })
                ->hideWhenUpdating(function ($request) {
                    return !is_null($this->geojson_url);
                })
                ->hideWhenCreating(function ($request) {
                    return !is_null($this->geojson_url);
                })->help('If Geojson URL is provided, no need to upload a Geojson file'),
            Text::make('Icon', 'icon', function () {
                return "<div style='width:64px;height:64px;'>" . $this->icon . "</div>";
            })->asHtml()->onlyOnDetail(),
            Swatches::make('Fill Color')->default(function () {
                return $this->app->primary_color;
            })->colors('text-advanced')->withProps([
                'show-fallback' => true,
                'fallback-type' => 'input',
            ])->hideWhenCreating(),
            Swatches::make('Stroke Color')->default(function () {
                return $this->app->primary_color;
            })->colors('text-advanced')->withProps([
                'show-fallback' => true,
                'fallback-type' => 'input',
            ])->hideWhenCreating(),
            Number::make('Stroke width')->hideWhenCreating(),
            Textarea::make('Icon SVG', 'icon')->onlyOnForms()->hideWhenCreating(),
            AttachMany::make('Layers', 'layers', Layer::class)
                ->showPreview()
                ->hideWhenCreating(),
            Text::make('Layers', function () {
                if (count($this->layers) > 0) {
                    return $this->layers->pluck('name')->implode("</;>");
                } else {
                    return 'No layers';
                }
            })->onlyOnDetail()->hideWhenCreating()->asHtml(),
            Code::Make('configuration')->language('json')->rules('json')->default(null)
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
        return [];
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

    public static function relatableLayers(NovaRequest $request, $query)
    {

        $resourceId = $request->resourceId;

        try {
            $resource = \App\Models\OverlayLayer::find($resourceId);
            $app_id = $resource->app_id;
            return $query->where('app_id', $app_id);
        } catch (\Throwable $th) {
            return $query->where('id', $resourceId);
        }
    }
}
