<?php

namespace App\Nova;

use Cdbeaton\BooleanTick\BooleanTick;
use Chaseconey\ExternalImage\ExternalImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Panel;
use NovaAttachMany\AttachMany;
use Waynestate\Nova\CKEditor;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;

class EcTrack extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\EcTrack::class;

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
        'author'
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
        $fields = [
            new Panel('Taxonomies', $this->attach_taxonomy()),
            Text::make(__('Name'), 'name')->sortable(),
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideWhenCreating()->hideWhenUpdating(),
            BelongsToMany::make('EcMedia'),
            CKEditor::make(__('Description'), 'description')->hideFromIndex(),
            Textarea::make(__('Excerpt'), 'excerpt')->help(
                __('Make it less than 255 characters')
            )->rows(2)->withMeta([
                'extraAttributes' => [
                    'maxlength' => 255,
                    'placeholder' => __('Make it less than 255 characters'),
                ]
            ])->hideFromIndex(),
            Text::make(__('Source'), 'source')->onlyOnDetail(),
            Text::make(__('Distance Comp'), 'distance_comp')->sortable()->hideWhenCreating()->hideWhenUpdating(),
            File::make('Geojson')->store(function (Request $request, $model) {
                $content = json_decode(file_get_contents($request->geojson));
                $geometry = DB::raw("(ST_GeomFromGeoJSON('" . json_encode($content->geometry) . "'))");
                return [
                    'geometry' => $geometry,
                ];
            })->hideFromDetail(),
            DateTime::make(__('Created At'), 'created_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            DateTime::make(__('Updated At'), 'updated_at')->sortable()->hideWhenUpdating()->hideWhenCreating(),
            WmEmbedmapsField::make(__('Map'), function ($model) {
                return [
                    'feature' => $model->getGeojson(),
                ];
            })->onlyOnDetail(),
            BelongsTo::make(__('Feature Image'), 'featureImage', EcMedia::class)->nullable()->onlyOnForms(),
            ExternalImage::make(__('Feature Image'), function () {
                $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                if ('' !== $url && substr($url, 0, 4) !== 'http') {
                    $url = Storage::disk('public')->url($url);
                }

                return $url;
            })->withMeta(['width' => 200])->hideWhenCreating()->hideWhenUpdating(),
            
            Text::make(__('Audio'), 'audio', function () {
                $pathinfo = pathinfo($this->model()->audio);
                if (isset($pathinfo['extension'])) {
                    $mime = CONTENT_TYPE_AUDIO_MAPPING[$pathinfo['extension']];
                }

                return $this->model()->audio ? '<audio controls><source src="' . $this->model()->audio . '" type="' . $mime . '">Your browser does not support the audio element.</audio>' : null;
            })->asHtml()->onlyOnDetail(),
            File::make(__('Audio'), 'audio')->store(function (Request $request, $model) {
                $file = $request->file('audio');
                $filename = sha1($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
                $cloudPath = 'ectrack/audio/' . $model->id . '/' . $filename;
                Storage::disk('s3')->put($cloudPath, file_get_contents($file));

                return Storage::cloud()->url($cloudPath);
            })->onlyOnForms(),
            BooleanTick::make(__('Audio'), 'audio')->showOnIndex(),
            AttachMany::make('EcMedia'),
            new Panel('Relations', $this->taxonomies()),
        ];

        return $fields;
    }

    protected function taxonomies()
    {
        return [
            MorphToMany::make('TaxonomyWheres'),
            MorphToMany::make('TaxonomyActivities'),
            MorphToMany::make('TaxonomyTargets'),
            MorphToMany::make('TaxonomyWhens'),
            MorphToMany::make('TaxonomyThemes'),
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
