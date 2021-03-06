<?php

namespace App\Nova;

use App\Nova\Actions\RegenerateEcMedia;
use Chaseconey\ExternalImage\ExternalImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Khalin\Nova\Field\Link;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Panel;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;

class EcMedia extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\EcMedia::class;
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

    public static function group()
    {
        return __('Editorial Content');
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
        $fields = [
            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name')->sortable(),
                Textarea::make(__('Description'), 'description')->rows(3)->hideFromIndex(),
            ]),
            MorphToMany::make('TaxonomyWheres'),
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideWhenCreating()->hideWhenUpdating(),
            Text::make(__('Excerpt'), 'excerpt')->onlyOnDetail(),
            Text::make(__('Source'), 'source')->onlyOnDetail(),
            Image::make('Url')->onlyOnForms()->hideWhenUpdating(),
            Text::make('Url', function () {
                $url = $this->model()->url;
                if (substr($url, 0, 4) !== 'http')
                    $url = Storage::disk('public')->url($url);

                return '<a href="' . $url . '" target="_blank">' . __('Original image') . '</a>';
            })->asHtml(),
            ExternalImage::make('Image', function () {
                $url = $this->model()->url;
                if (substr($url, 0, 4) !== 'http')
                    $url = Storage::disk('public')->url($url);

                return $url;
            })->withMeta(['width' => 500]),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            DateTime::make(__('Updated At'), 'updated_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                ];
            })->onlyOnDetail(),

            Link::make('geojson', 'id')->hideWhenUpdating()->hideWhenCreating()
                ->url(function () {
                    return isset($this->id) ? route('api.ec.media.geojson', ['id' => $this->id]) : '';
                })
                ->text(__('Open GeoJson'))
                ->icon()
                ->blank(),
        ];

        if (isset($this->model()->thumbnails)) {
            $fields[] = Panel::make("Thumbnails", $this->_getThumbnailsFields());
        }

        return $fields;
    }

    /**
     * Create the thumbnails fields to show in the EcMedia details
     *
     * @return array with the thumbnails fields
     */
    private function _getThumbnailsFields(): array
    {
        $model = $this->model();
        $thumbnails = json_decode($model->thumbnails, true);
        $fields = [];

        if (isset($thumbnails)) {
            foreach ($thumbnails as $size => $url) {
                $fields[] = ExternalImage::make($size, function () use ($url) {
                    return $url;
                })->onlyOnDetail();
            }
        }

        return $fields;
    }

    /**
     * Get the cards available for the request.
     *
     * @param Request $request
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
     * @param Request $request
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
     * @param Request $request
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
     * @param Request $request
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            new RegenerateEcMedia(),
        ];
    }
}
