<?php

namespace App\Nova;

use App\Providers\WmpIconProvider;
use Bernhardh\NovaIconSelect\IconProvider;
use Bernhardh\NovaIconSelect\NovaIconSelect;
use Chaseconey\ExternalImage\ExternalImage;
use ElevateDigital\CharcountedFields\TextareaCounted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\MorphOne;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Panel;
use Robertboes\NovaSliderField\NovaSliderField;
use Waynestate\Nova\CKEditor;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;
use Yna\NovaSwatches\Swatches;

class TaxonomyWhere extends Resource
{

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static string $model = \App\Models\TaxonomyWhere::class;
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
        'admin_level',
        'author'
    ];

    public static function group()
    {
        return __('Taxonomies');
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
        return [
            Text::make(__('Name'), 'name')->sortable(),
            Text::make(__('Identifier'), 'identifier'),
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideWhenCreating()->hideWhenUpdating(),
            CKEditor::make(__('Description'), 'description')->hideFromIndex(),
            Swatches::make('Color'),
            Number::make('Zindex')->hideFromIndex(),
            NovaIconSelect::make("Icon")->setIconProvider(WmpIconProvider::class),
            TextareaCounted::make(__('Excerpt'), 'excerpt')->hideFromIndex()->maxChars(255)->warningAt(200)->withMeta(['maxlength' => '255']),
            Text::make(__('Source ID'), 'source_id')->sortable()->hideWhenCreating()->hideWhenUpdating(),
            Text::make(__('Source'), 'source')->hideWhenCreating()->hideWhenUpdating(),
            Text::make(__('Import method'), 'import_method')->sortable()->hideWhenCreating()->hideWhenUpdating(),
            Number::make(__('Admin level'), 'admin_level')->sortable()->hideFromIndex(),
            BelongsTo::make(__('Feature Image'), 'featureImage', EcMedia::class)->nullable()->onlyOnForms(),
            ExternalImage::make(__('Feature Image'), function () {
                $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                if ('' !== $url && substr($url, 0, 4) !== 'http') {
                    $url = Storage::disk('public')->url($url);
                }
                return $url;
            })->withMeta(['width' => 200])->hideWhenCreating()->hideWhenUpdating()->hideFromIndex(),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating()->hideFromIndex(),
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                ];
            })->onlyOnDetail(),

            new Panel('UX/UI', $this->ux_ui_panel()),
        ];
    }

    protected function ux_ui_panel()
    {
        return [
            NovaSliderField::make(__('Stroke Width'), 'stroke_width')->min(0.5)->max(10)->default(2.5)->interval(0.5)->onlyOnForms(),
            NovaSliderField::make(__('Stroke Opacity'), 'stroke_opacity')->min(0)->max(1)->default(0)->interval(0.01)->onlyOnForms(),
            Text::make(__('Line Dash'), 'line_dash')->help('IMPORTANT : Write numbers with " , " separator')->hideFromIndex(),
            NovaSliderField::make(__('Min Visible Zoom'), 'min_visible_zoom')->min(5)->max(19)->default(5)->onlyOnForms(),
            NovaSliderField::make(__('Max Sizes Zoom'), 'min_size_zoom')->min(5)->max(19)->default(19)->onlyOnForms(),
            NovaSliderField::make(__('Min Size'), 'min_size')->min(0.1)->max(4)->default(1)->onlyOnForms(),
            NovaSliderField::make(__('Max Size'), 'max_size')->min(0.1)->max(4)->default(4)->onlyOnForms(),
            NovaSliderField::make(__('Icon Zoom'), 'icon_zoom')->min(5)->max(19)->default(0)->onlyOnForms(),
            NovaSliderField::make(__('Icon Size'), 'icon_size')->min(0.1)->max(4)->default(0.1)->onlyOnForms(),

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
     * @param Request $request
     *
     * @return array
     */
    public function cards(Request $request): array
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
    public function filters(Request $request): array
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
    public function lenses(Request $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function actions(Request $request): array
    {
        return [];
    }
}
