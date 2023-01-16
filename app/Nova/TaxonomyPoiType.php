<?php

namespace App\Nova;

use App\Providers\WebmappAppIconProvider;
use Bernhardh\NovaIconSelect\NovaIconSelect;
use Chaseconey\ExternalImage\ExternalImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Robertboes\NovaSliderField\NovaSliderField;
use Tsungsoft\ErrorMessage\ErrorMessage;
use Waynestate\Nova\CKEditor;
use Yna\NovaSwatches\Swatches;

class TaxonomyPoiType extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\TaxonomyPoiType::class;
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
        'identifier'
    ];

    public static function group()
    {
        return __('Taxonomies');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ErrorMessage::make('Error'),

            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name')->sortable(),
                CKEditor::make(__('Description'), 'description')->hideFromIndex(),
                Textarea::make(__('Excerpt'), 'excerpt')->hideFromIndex()->withMeta(['maxlength' => '255']),
            ]),

            Text::make(__('Identifier'), 'identifier'),
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideWhenCreating()->hideWhenUpdating(),
            Swatches::make('Color')->colors(["#f0f8ff", "#faebd7", "#00ffff", "#7fffd4", "#f0ffff", "#f5f5dc", "#ffe4c4", "#000000", "#ffebcd", "#0000ff", "#8a2be2", "#a52a2a", "#deb887", "#5f9ea0", "#7fff00", "#d2691e", "#ff7f50", "#6495ed", "#fff8dc", "#dc143c", "#00ffff", "#00008b", "#008b8b", "#b8860b", "#a9a9a9", "#006400", "#a9a9a9", "#bdb76b", "#8b008b", "#556b2f", "#ff8c00", "#9932cc", "#8b0000", "#e9967a", "#8fbc8f", "#483d8b", "#2f4f4f", "#2f4f4f", "#00ced1", "#9400d3", "#ff1493", "#00bfff", "#696969", "#696969", "#1e90ff", "#b22222", "#fffaf0", "#228b22", "#ff00ff", "#dcdcdc", "#f8f8ff", "#daa520", "#ffd700", "#808080", "#008000", "#adff2f", "#808080", "#f0fff0", "#ff69b4", "#cd5c5c", "#4b0082", "#fffff0", "#f0e68c", "#fff0f5", "#e6e6fa", "#7cfc00", "#fffacd", "#add8e6", "#f08080", "#e0ffff", "#fafad2", "#d3d3d3", "#90ee90", "#d3d3d3", "#ffb6c1", "#ffa07a", "#20b2aa", "#87cefa", "#778899", "#778899", "#b0c4de", "#ffffe0", "#00ff00", "#32cd32", "#faf0e6", "#ff00ff", "#800000", "#66cdaa", "#0000cd", "#ba55d3", "#9370db", "#3cb371", "#7b68ee", "#00fa9a", "#48d1cc", "#c71585", "#191970", "#f5fffa", "#ffe4e1", "#ffe4b5", "#ffdead", "#000080", "#fdf5e6", "#808000", "#6b8e23", "#ffa500", "#ff4500", "#da70d6", "#eee8aa", "#98fb98", "#afeeee", "#db7093", "#ffefd5", "#ffdab9", "#cd853f", "#ffc0cb", "#dda0dd", "#b0e0e6", "#800080", "#663399", "#ff0000", "#bc8f8f", "#4169e1", "#8b4513", "#fa8072", "#f4a460", "#2e8b57", "#fff5ee", "#a0522d", "#c0c0c0", "#87ceeb", "#6a5acd", "#708090", "#708090", "#fffafa", "#00ff7f", "#4682b4", "#d2b48c", "#008080", "#d8bfd8", "#ff6347", "#40e0d0", "#ee82ee", "#f5deb3", "#ffffff", "#f5f5f5", "#ffff00", "#9acd32"]),
            Number::make('Zindex')->hideFromIndex(),
            NovaIconSelect::make("Icon")->setIconProvider(WebmappAppIconProvider::class),
            Text::make(__('Source'), 'source')->hideWhenCreating()->hideWhenUpdating(),
            BelongsTo::make(__('Feature Image'), 'featureImage', EcMedia::class)->nullable()->searchable()->onlyOnForms(),
            ExternalImage::make(__('Feature Image'), function () {
                $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                if ('' !== $url && substr($url, 0, 4) !== 'http') {
                    $url = Storage::disk('public')->url($url);
                }

                return $url;
            })->withMeta(['width' => 200])->hideWhenCreating()->hideWhenUpdating()->hideFromIndex(),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            DateTime::make(__('Updated At'), 'updated_at')->sortable()->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),

            // new Panel('UX/UI', $this->ux_ui_panel()),
        ];
    }

    protected function ux_ui_panel()
    {
        return [
            NovaSliderField::make(__('Stroke Width'), 'stroke_width')->min(0.5)->max(10)->default(2.5)->interval(0.5)->onlyOnForms(),
            NovaSliderField::make(__('Stroke Opacity'), 'stroke_opacity')->min(0)->max(1)->default(0)->interval(0.01)->onlyOnForms(),
            Text::make(__('Line Dash'), 'line_dash')->help('IMPORTANT : Write numbers with " , " separator')->hideFromIndex(),
            NovaSliderField::make(__('Min Visible Zoom'), 'min_visible_zoom')->min(5)->max(19)->default(5)->onlyOnForms(),
            NovaSliderField::make(__('Max Size Zoom'), 'min_size_zoom')->min(5)->max(19)->default(15)->onlyOnForms(),
            NovaSliderField::make(__('Min Size'), 'min_size')->min(0.1)->max(4)->default(1)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Max Size'), 'max_size')->min(0.1)->max(4)->default(2)->interval(0.1)->onlyOnForms(),
            NovaSliderField::make(__('Icon Zoom'), 'icon_zoom')->min(5)->max(19)->default(15)->onlyOnForms(),
            NovaSliderField::make(__('Icon Size'), 'icon_size')->min(0.1)->max(4)->default(01.7)->interval(0.1)->onlyOnForms(),

            Number::make(__('Stroke Width'), 'stroke_width')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Stroke Opacity'), 'stroke_opacity')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Min Visible Zoom'), 'min_visible_zoom')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Max Size Zoom'), 'min_size_zoom')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Min Size'), 'min_size')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Max Size'), 'max_size')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Icon Zoom'), 'icon_zoom')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            Number::make(__('Icon Size'), 'icon_size')->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
