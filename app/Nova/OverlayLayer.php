<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
use Laravel\Nova\Fields\Heading;

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
        'id',
        'name'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        $app_name = $this->app_id;

        return [
            ID::make(__('ID'), 'id')->sortable(),

            Text::make('name')
                ->help(__('Name of the overlay in GeoHub. To change the name displayed in the app under the overlay filter menu, modify the label below.')),
            Heading::make('<p>Name: This is the name of the overlay as it appears in GeoHub. To change the display name in the app’s overlay filter menu, you need to modify the "Label" field.</p>')->asHtml(),

            NovaTabTranslatable::make([
                Text::make(__('Label'), 'label')
                    ->help(__('Name displayed in the overlay layers filter menu.'))
            ]),
            Heading::make('<p>Label: The name displayed in the overlay layers filter menu.</p>')->asHtml(),

            Boolean::make('Show this overlay by default', 'default')
                ->hideFromIndex()
                ->hideWhenCreating()
                ->help(__('Enable this option to show the overlay by default on the map.')),
            Heading::make('<p>Show this overlay by default: This setting enables the overlay to be displayed by default on the map.</p>')->asHtml(),

            BelongsTo::make('App', 'app', App::class)
                ->searchable()
                ->hideFromIndex()
                ->help(__('This indicates the app that will display the overlay.')),
            Heading::make('<p>App: The app associated with this overlay, which will display it.</p>')->asHtml(),

            Text::make('Geojson URL', 'feature_collection')
                ->hideWhenCreating(function () {
                    return $this->featureCollectionFileExist();
                })
                ->hideWhenUpdating(function () {
                    return $this->featureCollectionFileExist();
                })
                ->hideFromDetail(function () {
                    return $this->featureCollectionFileExist();
                })
                ->rules('nullable', 'url')
                ->displayUsing(function ($value) {
                    return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
                })->asHtml()
                ->hideFromIndex()
                ->help(__('Enter the GeoJson URL. Alternatively, you can delete the existing GeoJson URL, click on "Update & Continue Editing," and upload an external GeoJson file. NOTE: If both fields are filled, the GeoJson File will be used.')),
            Heading::make('<p>Geojson URL: This is the URL for the GeoJson file associated with the overlay. If both this URL and a file are provided, the file will be used instead.</p>')->asHtml(),

            File::make('Geojson File', 'feature_collection')
                ->disk('public')
                ->path('geojson/' . $app_name)
                ->acceptedTypes(['.json', '.geojson'])
                ->storeAs(function (Request $request) {
                    return $request->feature_collection->getClientOriginalName();
                })
                ->hideWhenCreating(function () {
                    return $this->getFeatureCollectionType() == 'external';
                })
                ->hideWhenUpdating(function () {
                    return $this->getFeatureCollectionType() == 'external';
                })
                ->hideFromDetail(function () {
                    return $this->getFeatureCollectionType() == 'external';
                })
                ->help(__('Upload a GeoJson file. Alternatively, you can delete the existing GeoJson file, click on "Update & Continue Editing," and insert an external GeoJson URL. NOTE: If both fields are filled, the GeoJson File will be used.')),

            Text::make('Icon', 'icon', function () {
                return "<div style='width:64px;height:64px;'>" . $this->icon . "</div>";
            })->asHtml(),
            Heading::make('<p>Icon: The SVG icon associated with this overlay.</p>')->asHtml(),

            Swatches::make('Fill Color')
                ->default(function () {
                    return $this->app->primary_color;
                })
                ->colors('text-advanced')
                ->withProps([
                    'show-fallback' => true,
                    'fallback-type' => 'input',
                ])
                ->hideWhenCreating()
                ->help(__('This is the fill color of the overlay.')),
            Heading::make('<p>Fill Color: The color used to fill the overlay on the map.</p>')->asHtml(),

            Swatches::make('Stroke Color')
                ->default(function () {
                    return $this->app->primary_color;
                })
                ->colors('text-advanced')
                ->withProps([
                    'show-fallback' => true,
                    'fallback-type' => 'input',
                ])
                ->hideWhenCreating()
                ->help(__('This is the border color of the overlay. It will also be applied when clicking on the overlay.')),
            Heading::make('<p>Stroke Color: The border color of the overlay, which is also applied on click.</p>')->asHtml(),

            Number::make('Stroke width')
                ->hideWhenCreating()
                ->help(__('This field determines the border thickness of the overlay.')),
            Heading::make('<p>Stroke Width: The thickness of the overlay’s border.</p>')->asHtml(),

            Textarea::make('Icon SVG', 'icon')
                ->onlyOnForms()
                ->hideWhenCreating()
                ->help(__('Insert the icon here in SVG format.')),

            AttachMany::make('Layers', 'layers', Layer::class)
                ->showPreview()
                ->hideWhenCreating()
                ->help(__('Select one or more layers to associate with the overlay. Click "Preview" to display the selected ones.')),

            Text::make('Layers', function () {
                if (count($this->layers) > 0) {
                    return $this->layers->pluck('name')->implode("</;>");
                } else {
                    return 'No layers';
                }
            })->asHtml(),
            Heading::make('<p>Layers: The layers associated with this overlay.</p>')->asHtml(),

            Code::Make('configuration')
                ->language('json')
                ->rules('json', 'nullable')
                ->default(null)
                ->help(__('Insert the JSON code of type FeatureCollection for the overlay here.')),
            Heading::make('<p>Configuration: JSON code of type FeatureCollection for the overlay.</p>')->asHtml(),
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


    /**
     * Check if the feature_collection field is an external url or a local file path
     * 
     * @return string
     */
    public function getFeatureCollectionType(): string
    {
        $result = '';
        if (isset($this->feature_collection)) {
            if (str_starts_with($this->feature_collection, 'http') && str_starts_with($this->feature_collection, 'https')) {
                $result = 'external';
            } else {
                $result = 'local';
            }
        }
        return $result;
    }

    /**
     * Check if the feature_collection file exist at the given path
     * 
     * @return bool
     */
    public function featureCollectionFileExist(): bool
    {
        $result = false;
        if ($this->getFeatureCollectionType() == 'local') {
            $result = Storage::disk('public')->exists($this->feature_collection);
        }
        return $result;
    }
}
