<?php

namespace App\Nova;

use Chaseconey\ExternalImage\ExternalImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Panel;
use NovaAttachMany\AttachMany;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;

class EcPoi extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\EcPoi::class;

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
        'author',
    ];

    public static function group()
    {
        return __('Editorial Content');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            new Panel('Taxonomies', $this->attach_taxonomy()),
            Text::make(__('Name'), 'name')->required()->sortable(),
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideWhenCreating()->hideWhenUpdating(),
            BelongsToMany::make('EcMedia'),
            Text::make(__('Description'), 'description')->hideFromIndex(),
            Text::make(__('Excerpt'), 'excerpt')->hideFromIndex(),
            Text::make(__('Contact phone'), 'contact_phone')->hideFromIndex(),
            Text::make(__('Contact email'), 'contact_email')->hideFromIndex(),
            Text::make(__('Related Urls'), 'related_url')->hideFromIndex()->hideFromDetail(),
            Text::make('Related Urls', function () {
                $urls = $this->model()->related_url;
                $html_url = '';
                $urls = explode(';', $urls);
                foreach ($urls as $url) {
                    $html_url .= '<a href="' . $url . '" target="_blank">' . $url . '</a><br>';
                }
                return $html_url;
            })->asHtml()->onlyOnDetail(),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            DateTime::make(__('Updated At'), 'updated_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            WmEmbedmapsField::make(__('Map'), 'geometry', function () {
                $model = $this->model();
                return [
                    'feature' => $model->id ? $model->getGeojson() : NULL,
                ];
            })->required()->hideFromIndex(),
            BelongsTo::make(__('Feature Image'), 'featureImage', EcMedia::class)->nullable()->onlyOnForms(),
            ExternalImage::make(__('Feature Image'), function () {
                $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                if ('' !== $url && substr($url, 0, 4) !== 'http') {
                    $url = Storage::disk('public')->url($url);
                }

                return $url;
            })->withMeta(['width' => 200])->hideWhenCreating()->hideWhenUpdating(),
            AttachMany::make('EcMedia'),
            /**
             * @todo: in progress
             */
            // File::make('audio')->store(function (Request $request, $model) {
            //     $content = json_decode(file_get_contents($request->geojson));
            //     $geometry = DB::raw("(ST_GeomFromGeoJSON('" . json_encode($content->audio) . "'))");
            //     return [
            //         'geometry' => $geometry,
            //     ];
            // })->hideFromIndex(),
            new Panel('Relations', $this->taxonomies()),
        ];
    }

    protected function taxonomies()
    {
        return [
            MorphToMany::make('TaxonomyWheres'),
            MorphToMany::make('TaxonomyActivities'),
            MorphToMany::make('TaxonomyTargets'),
            MorphToMany::make('TaxonomyWhens'),
            MorphToMany::make('TaxonomyThemes'),
            MorphToMany::make('TaxonomyPoiTypes'),
        ];
    }

    protected function attach_taxonomy()
    {
        return [
            AttachMany::make('TaxonomyWheres'),
            AttachMany::make('TaxonomyActivities'),
            AttachMany::make('TaxonomyTargets'),
            AttachMany::make('TaxonomyWhens'),
            AttachMany::make('TaxonomyThemes'),
            AttachMany::make('TaxonomyPoiTypes'),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param \Illuminate\Http\Request $request
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
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
