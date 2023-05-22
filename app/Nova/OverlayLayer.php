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
use Nova\Multiselect\Multiselect;

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
            BelongsTo::make('App', 'app', App::class)
                ->searchable()
                ->hideFromIndex(),
            File::make('File', 'feature_collection')
                ->disk('public')
                ->path('geojson/' . $app_name)
                ->acceptedTypes(['.json', '.geojson'])
                //rename the file taking the name property from the request
                ->storeAs(function (Request $request) {
                    return $request->feature_collection->getClientOriginalName();
                })
                ->hideWhenCreating(),
            Text::make('Icon', 'icon', function () {
                return "<div style='width:64px;height:64px;'>" . $this->icon . "</div>";
            })->asHtml()->onlyOnDetail(),
            Textarea::make('Icon SVG', 'icon')->onlyOnForms()->hideWhenCreating(),
            AttachMany::make('Layers', 'layers', Layer::class)
                ->hideFromIndex()
                ->hideWhenCreating(),
            //TODO add a method to filter the choices in the select field
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
}
