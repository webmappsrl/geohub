<?php

namespace App\Nova;

use App\Nova\Actions\RegenerateEcTrack;
use Chaseconey\ExternalImage\ExternalImage;
use ElevateDigital\CharcountedFields\TextareaCounted;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Khalin\Nova\Field\Link;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Panel;
use NovaAttachMany\AttachMany;
use Waynestate\Nova\CKEditor;
use Webmapp\EcMediaPopup\EcMediaPopup;
use Webmapp\Ecpoipopup\Ecpoipopup;
use Webmapp\FeatureImagePopup\FeatureImagePopup;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;

class EcTrack extends Resource {
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static string $model = \App\Models\EcTrack::class;
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
        return __('Editorial Content');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function fields(Request $request): array {
        try {
            $geojson = $this->model()->getGeojson();
        } catch (Exception $e) {
            $geojson = [];
        }

        return [
            new Panel('Taxonomies', $this->attach_taxonomy()),

            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name')->sortable(),
                CKEditor::make(__('Description'), 'description')->hideFromIndex(),
                TextareaCounted::make(__('Excerpt'), 'excerpt')->hideFromIndex()->maxChars(255)->warningAt(200)->withMeta(['maxlength' => '255']),
                Text::make(__('Difficulty'), 'difficulty')->sortable(),
            ]),

            Text::make(__('Import Method'), 'import_method'),
            Text::make(__('Source ID'), 'source_id'),
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideWhenCreating()->hideWhenUpdating(),
            BelongsToMany::make('EcMedia')->onlyOnDetail(),
            BelongsToMany::make('EcPois')->onlyOnDetail(),
            EcMediaPopup::make(__('EcMedia'), 'ecMedia')
                ->onlyOnForms()
                ->feature($geojson)
                ->apiBaseUrl('/api/ec/track/'),
            Text::make(__('Source'), 'source')->onlyOnDetail(),
            Text::make(__('Distance Comp'), 'distance_comp')->sortable()->hideWhenCreating()->hideWhenUpdating(),
            File::make('Geojson')->store(function (Request $request, $model) {
                $content = file_get_contents($request->geojson);
                $geometry = $model->fileToGeometry($content);

                return $geometry ? [
                    'geometry' => $geometry,
                ] : function () {
                    throw new Exception(__("The uploaded file is not valid"));
                };
            })->hideFromDetail(),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            DateTime::make(__('Updated At'), 'updated_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            WmEmbedmapsField::make(__('Map'), 'geometry', function () {
                return [
                    'feature' => $this->getGeojson(),
                ];
            })->hideFromIndex()->hideWhenCreating()->viewOnly(),
            FeatureImagePopup::make(__('Feature Image'), 'featureImage')->onlyOnForms()->feature($geojson)->apiBaseUrl('/api/ec/track/'),
            Ecpoipopup::make(__('EcPoi'))->nullable()->onlyOnForms()->feature($geojson),
            ExternalImage::make(__('Feature Image'), function () {
                $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                if ('' !== $url && substr($url, 0, 4) !== 'http') {
                    $url = Storage::disk('public')->url($url);
                }

                return $url;
            })->withMeta(['width' => 200])->hideWhenCreating()->hideWhenUpdating(),

            Text::make(__('Audio'), 'audio', function () {
                $pathInfo = pathinfo($this->model()->audio);
                if (isset($pathInfo['extension'])) {
                    $mime = CONTENT_TYPE_AUDIO_MAPPING[$pathInfo['extension']];
                } else $mime = CONTENT_TYPE_AUDIO_MAPPING['mp3'];

                return $this->model()->audio ? '<audio controls><source src="' . $this->model()->audio . '" type="' . $mime . '">Your browser does not support the audio element.</audio>' : null;
            })->asHtml()->onlyOnDetail(),
            File::make(__('Audio'), 'audio')->store(function (Request $request, $model) {
                $file = $request->file('audio');

                return $model->uploadAudio($file);
            })->acceptedTypes('audio/*')->onlyOnForms(),
            Boolean::make(__('Audio'), 'audio')->onlyOnIndex(),

            Link::make('geojson', 'id')->hideWhenUpdating()->hideWhenCreating()
                ->url(function () {
                    return isset($this->id) ? route('api.ec.track.view.geojson', ['id' => $this->id]) : '';
                })
                ->text(__('Open GeoJson'))
                ->icon()
                ->blank(),

            new Panel('Relations', $this->taxonomies()),
        ];
    }

    protected function taxonomies(): array {
        return [
            MorphToMany::make('TaxonomyWheres'),
            MorphToMany::make('TaxonomyActivities'),
            MorphToMany::make('TaxonomyTargets'),
            MorphToMany::make('TaxonomyWhens'),
            MorphToMany::make('TaxonomyThemes'),
        ];
    }

    protected function attach_taxonomy(): array {
        return [
            AttachMany::make('TaxonomyWheres'),
            AttachMany::make('TaxonomyActivities'),
            AttachMany::make('TaxonomyTargets'),
            AttachMany::make('TaxonomyWhens'),
            AttachMany::make('TaxonomyThemes'),
        ];
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
        return [
            new RegenerateEcTrack(),
        ];
    }
}
