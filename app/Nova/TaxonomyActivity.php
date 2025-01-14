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
use Laravel\Nova\Fields\MorphedByMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Robertboes\NovaSliderField\NovaSliderField;
use Waynestate\Nova\CKEditor;
use Yna\NovaSwatches\Swatches;

class TaxonomyActivity extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\TaxonomyActivity::class;

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
        'identifier',
    ];

    public static function group()
    {
        return __('Taxonomies');
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(Request $request): array
    {
        return [

            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name')
                    ->sortable()
                    ->help(__('Name displayed of the taxonomy')),
                Heading::make('<p>Name: This is the name displayed throughout the app.</p>')->asHtml(),
                CKEditor::make(__('Description'), 'description')
                    ->hideFromIndex()
                    ->help(__('Enter a detailed description of the taxonomy.'))
                    ->hideFromIndex()
                    ->hideFromDetail()
                    ->hideWhenCreating()
                    ->hideWhenUpdating(),
                Textarea::make(__('Excerpt'), 'excerpt')->hideFromIndex()->withMeta(['maxlength' => '255'])
                    ->help(__('Provide a brief summary or excerpt for the taxonomy. This should be a concise description.'))
                    ->hideFromIndex()
                    ->hideFromDetail()
                    ->hideWhenCreating()
                    ->hideWhenUpdating(),
            ]),
            Text::make(__('Identifier'), 'identifier')
                ->updateRules('unique:taxonomy_activities,identifier,{{resourceId}}')
                ->help(__('This is the API identifier for the taxonomy.')),
            BelongsTo::make('Author', 'author', User::class)
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->help(__('The user who created this taxonomy.')),
            Swatches::make('Color')
                ->colors('text-advanced')->withProps([
                    'show-fallback' => true,
                    'fallback-type' => 'input',
                ])
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Number::make('Zindex')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Text::make('Icon', function () {
                return $this->icon;
            })
                ->asHtml()
                ->onlyOnIndex(),
            NovaIconSelect::make('Icon Label', 'icon')
                ->setIconProvider(WebmappAppIconProvider::class)
                ->help(__('Select an icon from the list to display for the activity.')),
            Text::make(__('Source'), 'source')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            BelongsTo::make(__('Feature Image'), 'featureImage', EcMedia::class)
                ->nullable()
                ->searchable()
                ->onlyOnForms()
                ->help(__('Select an image from Ec Medias to use as the feature image. If selected, this image will be shown by default in layers that use this activity taxonomy but do not have their own feature image.')),
            ExternalImage::make(__('Feature Image'), function () {
                $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                if ($url !== '' && substr($url, 0, 4) !== 'http') {
                    $url = Storage::disk('public')->url($url);
                }

                return $url;
            })
                ->withMeta(['width' => 200])
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->hideFromIndex()
                ->help(__('The main image associated with this taxonomy.')),
            DateTime::make(__('Created At'), 'created_at')
                ->sortable()
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->hideFromIndex()
                ->help(__('The date and time when this taxonomy was created.')),
            DateTime::make(__('Updated At'), 'updated_at')
                ->sortable()
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->hideFromIndex()
                ->help(__('The date and time when this taxonomy was last updated.')),
            MorphedByMany::make(__('Tracks'), 'ecTracks', EcTrack::class)
                ->searchable(),
        ];
    }

    protected function ux_ui_panel()
    {
        return [
            NovaSliderField::make(__('Stroke Width'), 'stroke_width')->min(0.5)->max(10)->default(2.5)->interval(0.5)->onlyOnForms()
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            NovaSliderField::make(__('Stroke Opacity'), 'stroke_opacity')->min(0)->max(1)->default(0)->interval(0.01)->onlyOnForms()
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            Text::make(__('Line Dash'), 'line_dash')->help('IMPORTANT : Write numbers with " , " separator')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            NovaSliderField::make(__('Min Visible Zoom'), 'min_visible_zoom')->min(5)->max(19)->default(5)->onlyOnForms()
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            NovaSliderField::make(__('Max Size Zoom'), 'min_size_zoom')->min(5)->max(19)->default(15)->onlyOnForms()
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            NovaSliderField::make(__('Min Size'), 'min_size')->min(0.1)->max(4)->default(1)->interval(0.1)->onlyOnForms()
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            NovaSliderField::make(__('Max Size'), 'max_size')->min(0.1)->max(4)->default(2)->interval(0.1)->onlyOnForms()
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            NovaSliderField::make(__('Icon Zoom'), 'icon_zoom')->min(5)->max(19)->default(15)->onlyOnForms()
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            NovaSliderField::make(__('Icon Size'), 'icon_size')->min(0.1)->max(4)->default(01.7)->interval(0.1)->onlyOnForms()
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

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
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
