<?php

namespace App\Nova;

use App\Helpers\NovaCurrentResourceActionHelper;
use App\Providers\WebmappAppIconProvider;
use Bernhardh\NovaIconSelect\NovaIconSelect;
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

class TaxonomyWhere extends Resource {
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
        'name',
    ];

    public static function group() {
        return __('Taxonomies');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function fields(Request $request): array {

        if(NovaCurrentResourceActionHelper::isIndex($request)) {
            return $this->index();
        }

        if(NovaCurrentResourceActionHelper::isDetail($request)) {
            return $this->detail();
        }

        if(NovaCurrentResourceActionHelper::isForm($request)) {
            return $this->form($request);
        }

    }

    private function index() {
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

    private function detail() {
        return [(new Tabs("Taxnonomy Where Details: {$this->name} ($this->id)",
        [
            'Main' => [
                Text::make('Geohub ID',function(){return $this->id;}),
                Text::make(__('Identifier'), 'identifier'),
                BelongsTo::make('Author', 'author', User::class),
                DateTime::make(__('Created At'), 'created_at'),
                DateTime::make(__('Updated At'), 'updated_at'),        
                NovaTabTranslatable::make([
                    Text::make(__('Name'), 'name'),
                    CKEditor::make(__('Description'), 'description'),
                    Textarea::make(__('Excerpt'), 'excerpt'),
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
            ],
            'Map' => [
                WmEmbedmapsField::make(__('Map'), function ($model) {
                    return [
                        'feature' => $model->getGeojson(),
                    ];
                }),    
            ],
            'Style' => [
                Number::make(__('Stroke Width'), 'stroke_width'),
                Number::make(__('Stroke Opacity'), 'stroke_opacity'),
                Text::make(__('Line Dash'), 'line_dash')->help('IMPORTANT : Write numbers with " , " separator'),
                Number::make(__('Min Visible Zoom'), 'min_visible_zoom'),
                Number::make(__('Max Size Zoom'), 'min_size_zoom'),
                Number::make(__('Min Size'), 'min_size'),
                Number::make(__('Max Size'), 'max_size'),
                Number::make(__('Icon Zoom'), 'icon_zoom'),
                Number::make(__('Icon Size'), 'icon_size'),
            ]
        ]
        ))->withToolbar()];
    }

    private function form($request) {

        try {
            $geojson = $this->model()->getGeojson();
        } catch (Exception $e) {
            $geojson = [];
        }

        $tab_title = "New Taxonomy Where";
        if(NovaCurrentResourceActionHelper::isUpdate($request)) {
            $tab_title = "Taxonomy Where Edit: {$this->name} ({$this->id})";
        }

        return [(new Tabs($tab_title,
        [
            'Main' => [
                Text::make(__('Identifier'), 'identifier'),
                NovaTabTranslatable::make([
                    Text::make(__('Name'), 'name'),
                    CKEditor::make(__('Description'), 'description'),
                    Textarea::make(__('Excerpt'), 'excerpt'),
                ]),     
            ],
            'Media' => [
                BelongsTo::make('Feature Image','featureImage',EcMedia::class)
                ->searchable()
                ->showCreateRelationButton()
                ->nullable(),
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
                }),
            ],
            'Style' => [
                Number::make(__('Stroke Width'), 'stroke_width'),
                Number::make(__('Stroke Opacity'), 'stroke_opacity'),
                Text::make(__('Line Dash'), 'line_dash')->help('IMPORTANT : Write numbers with " , " separator'),
                Number::make(__('Min Visible Zoom'), 'min_visible_zoom'),
                Number::make(__('Max Size Zoom'), 'min_size_zoom'),
                Number::make(__('Min Size'), 'min_size'),
                Number::make(__('Max Size'), 'max_size'),
                Number::make(__('Icon Zoom'), 'icon_zoom'),
                Number::make(__('Icon Size'), 'icon_size'),
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
    public function cards(Request $request): array {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function filters(Request $request): array {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function lenses(Request $request): array {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function actions(Request $request): array {
        return [];
    }
}
