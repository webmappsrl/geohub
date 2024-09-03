<?php

namespace App\Nova;

use App\Helpers\NovaCurrentResourceActionHelper;
use Chaseconey\ExternalImage\ExternalImage;
use Eminiarts\Tabs\Tabs;
use Eminiarts\Tabs\TabsOnEdit;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Panel;
use Robertboes\NovaSliderField\NovaSliderField;
use Waynestate\Nova\CKEditor;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;
use Yna\NovaSwatches\Swatches;
use Tsungsoft\ErrorMessage\ErrorMessage;
use Webmapp\FeatureImagePopup\FeatureImagePopup;
use Laravel\Nova\Fields\Heading;

class TaxonomyWhere extends Resource
{
    use TabsOnEdit;
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
        'name'
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

        if (NovaCurrentResourceActionHelper::isIndex($request)) {
            return $this->index();
        }

        if (NovaCurrentResourceActionHelper::isDetail($request)) {
            return $this->detail();
        }

        if (NovaCurrentResourceActionHelper::isForm($request)) {
            return $this->form($request);
        }
    }

    private function index()
    {
        return [

            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name'),
            ]),
            Text::make(__('Identifier'), 'identifier'),
            BelongsTo::make('Author', 'author', User::class)->sortable(),
            DateTime::make(__('Created At'), 'created_at')->sortable(),
            DateTime::make(__('Updated At'), 'updated_at')->sortable(),
        ];
    }

    private function detail()
    {
        return [(new Tabs(
            "Taxonomy Where Details: {$this->name} ({$this->id})",
            [
                'Main' => [
                    Text::make('Geohub ID', function () {
                        return $this->id;
                    }),
                    Heading::make('<p>Geohub ID: The unique identifier for the track in Geohub.</p>')->asHtml(),
                    Text::make(__('Identifier'), 'identifier'),
                    Heading::make('<p>Identifier: Api identifier.</p>')->asHtml(),
                    BelongsTo::make('Author', 'author', User::class),
                    Heading::make('<p>Author: The user who created the taxonomy.</p>')->asHtml(),
                    DateTime::make(__('Created At'), 'created_at'),
                    Heading::make('<p>Created At: The date and time when the track was created.</p>')->asHtml(),
                    DateTime::make(__('Updated At'), 'updated_at'),
                    Heading::make('<p>Updated At: The date and time when the track was last modified.</p>')->asHtml(),
                    NovaTabTranslatable::make([
                        Text::make(__('Name'), 'name')
                            ->sortable()
                            ->help(__('Name displayed of the taxonomy')),
                        Heading::make('<p>Name: This is the name displayed for the taxonomy.</p>')->asHtml(),
                        CKEditor::make(__('Description'), 'description')
                            ->hideFromIndex()
                            ->hideFromDetail()
                            ->hideWhenCreating()
                            ->hideWhenUpdating(),
                        Textarea::make(__('Excerpt'), 'excerpt')
                            ->hideFromIndex()
                            ->hideFromDetail()
                            ->hideWhenCreating()
                            ->hideWhenUpdating(),
                    ]),
                ],
                'Media' => [
                    ExternalImage::make(__('Feature Image'), function () {
                        $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                        if ('' !== $url && substr($url, 0, 4) !== 'http') {
                            $url = Storage::disk('public')->url($url);
                        }

                        return $url;
                    })->withMeta(['width' => 200]),
                    Heading::make('<p>Feature Image: The main image representing the taxonomy.</p>')->asHtml(),
                ],
                'Map' => [
                    WmEmbedmapsField::make(__('Map'), function ($model) {
                        return [
                            'feature' => $model->getGeojson(),
                        ];
                    }),
                    Heading::make('<p>Map: The geometry of taxonomy content that define the area on the map.</p>')->asHtml(),
                ],
                'Style' => [
                    Number::make(__('Stroke Width'), 'stroke_width')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Stroke Opacity'), 'stroke_opacity')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Text::make(__('Line Dash'), 'line_dash')
                        ->help('IMPORTANT : Write numbers with " , " separator')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Min Visible Zoom'), 'min_visible_zoom')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Max Size Zoom'), 'min_size_zoom')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Min Size'), 'min_size')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Max Size'), 'max_size')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Icon Zoom'), 'icon_zoom')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Icon Size'), 'icon_size')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                ]
            ]
        ))->withToolbar()];
    }

    private function form($request)
    {

        try {
            $geojson = $this->model()->getGeojson();
        } catch (Exception $e) {
            $geojson = [];
        }

        $tab_title = "New Taxonomy Where";
        if (NovaCurrentResourceActionHelper::isUpdate($request)) {
            $tab_title = "Taxonomy Where Edit: {$this->name} ({$this->id})";
        }

        return [(new Tabs(
            $tab_title,
            [
                'Main' => [
                    Text::make(__('Identifier'), 'identifier')
                        ->help(__('API Identifier. To change the name displayed in the app, modify the label below.')),
                    NovaTabTranslatable::make([
                        Heading::make('
                                <p>Name* displayed through the app.</p>
                            ')->asHtml(),
                        Text::make(__('Name'), 'name')
                            ->help(__('Name displayed of the taxonomy')),
                        CKEditor::make(__('Description'), 'description')
                            ->hideFromIndex()
                            ->hideFromDetail()
                            ->hideWhenCreating()
                            ->hideWhenUpdating(),
                        Textarea::make(__('Excerpt'), 'excerpt')
                            ->hideFromIndex()
                            ->hideFromDetail()
                            ->hideWhenCreating()
                            ->hideWhenUpdating(),
                    ]),
                ],
                'Media' => [
                    BelongsTo::make('Feature Image', 'featureImage', EcMedia::class)
                        ->searchable()
                        ->showCreateRelationButton()
                        ->nullable()
                        ->help(__('Select an image from Ec Medias or upload one by clicking on "Create".')),
                ],
                'Map' => [
                    File::make('Geojson')->store(function (Request $request, $model) {
                        $content = file_get_contents($request->geojson);
                        $geometry = $model->fileToGeometry($content);

                        return $geometry ? [
                            'geometry' => $geometry,
                        ] : function () {
                            throw new Exception(__("The uploaded file is not valid"));
                        };
                    })
                        ->help(__('Here you can upload a GeoJSON file of a polygon, which is a closed geometry that defines an area on a map.')),
                ],
                'Style' => [
                    Number::make(__('Stroke Width'), 'stroke_width')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Stroke Opacity'), 'stroke_opacity')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Text::make(__('Line Dash'), 'line_dash')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Min Visible Zoom'), 'min_visible_zoom')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Max Size Zoom'), 'min_size_zoom')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Min Size'), 'min_size')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Max Size'), 'max_size')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Icon Zoom'), 'icon_zoom')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                    Number::make(__('Icon Size'), 'icon_size')
                        ->hideFromIndex()
                        ->hideFromDetail()
                        ->hideWhenCreating()
                        ->hideWhenUpdating(),
                ]
            ]
        ))->withToolbar()];
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
