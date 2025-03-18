<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use NovaAttachMany\AttachMany;
use Yna\NovaSwatches\Swatches;

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
        'name',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(Request $request)
    {
        // $app_name = str_replace(' ','-',strtolower($this->app->name));
        $app_name = $this->app_id;

        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make('name')
                ->help(__('Name of the overlay in GeoHub. To change the name displayed in the app under the overlay filter menu, modify the label below.')),
            Heading::make('<p>Label: Name displayed in the overlay layers filter menu.</p>')
                ->asHtml()
                ->onlyOnForms(),
            NovaTabTranslatable::make([
                Text::make(__('Label'), 'label'),
            ]),
            Boolean::make('Show this overlay by default', 'default')
                ->hideFromIndex()
                ->hideWhenCreating()
                ->help(__('Turn this option on if you want to show this overlay by default on the map')),
            BelongsTo::make('App', 'app', App::class)
                ->searchable()
                ->hideFromIndex()
                ->help(__('Associated app that will display the overlay.')),
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
                    $html = <<<HTML
                    <a href="{$value}" target="_blank">{$value}</a>
                HTML;

                    return $html;
                })->asHtml()
                ->hideFromIndex()
                ->help(__('Enter the GeoJson URL. Alternatively you can delete the existent Geojson URL, click on "Update & Continue Editing" below, and upload an external Geojson file. NOTE: If both fields are filled, the GeoJson File will be used.')),
            File::make('Geojson File', 'feature_collection')
                ->disk('public')
                ->path('geojson/'.$app_name)
                ->acceptedTypes(['.json', '.geojson'])
                // rename the file taking the name property from the request
                ->storeAs(function (Request $request) {
                    return $request->feature_collection->getClientOriginalName();
                })
                ->hideWhenCreating(function () {
                    // if the feature_collection is an external url, hide the file field
                    return $this->getFeatureCollectionType() == 'external';
                })
                ->hideWhenUpdating(function () {
                    return $this->getFeatureCollectionType() == 'external';
                })
                ->hideFromDetail(function () {
                    return $this->getFeatureCollectionType() == 'external';
                })
                ->help('Upload a Geojson file. Alternatively you can delete the existent Geojson file, click on "Update & Continue Editing" below, and insert an external Geojson URL. NOTE: If both fields are filled, the GeoJson File will be used.'),
            Text::make('Icon', 'icon', function () {
                return "<div style='width:64px;height:64px;'>".$this->icon.'</div>';
            })
                ->asHtml()
                ->onlyOnDetail()
                ->help(__('Icon associated with this overlay.')),
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
                ->help(__('Fill color of the overlay.')),
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
                ->help(__('Border color of the overlay. This color will also be applied when clicking on an overlay.')),
            Number::make('Stroke width')
                ->hideWhenCreating()
                ->help(__('Border thickness.')),
            Textarea::make('Icon SVG', 'icon')
                ->onlyOnForms()
                ->hideWhenCreating()
                ->help(__('Insert the icon here in SVG format.')),
            AttachMany::make('Layers', 'layers', Layer::class)
                ->showPreview()
                ->hideWhenCreating()
                ->help(__('Select one or more layers to associate with the overlay. Click "Preview" to display the selected ones.')),
            Text::make('Layers', function () {
                $help = '<p>Layers: The layers associated with this overlay.</p>';
                if (count($this->layers) > 0) {
                    return $this->layers->pluck('name')->implode('</;>').$help;
                } else {
                    return 'No layers'.$help;
                }
            })
                ->onlyOnDetail()
                ->hideWhenCreating()
                ->asHtml(),
            Code::Make('configuration')
                ->language('json')
                ->rules('json', 'nullable')
                ->default(null)
                ->help(__('Insert the JSON code of type FeatureCollection for the overlay here.')),
        ];
    }

    /**
     * Get the cards available for the request.
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
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
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
