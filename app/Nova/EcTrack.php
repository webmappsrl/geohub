<?php

namespace App\Nova;

use App\Helpers\NovaCurrentResourceActionHelper;
use App\Nova\Actions\RegenerateEcTrack;
use Chaseconey\ExternalImage\ExternalImage;
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
use NovaAttachMany\AttachMany;
use Webmapp\EcMediaPopup\EcMediaPopup;
use Webmapp\Ecpoipopup\Ecpoipopup;
use Webmapp\FeatureImagePopup\FeatureImagePopup;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;
use Eminiarts\Tabs\Tabs;
use Eminiarts\Tabs\TabsOnEdit;
use Laravel\Nova\Fields\KeyValue;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Textarea;
use Titasgailius\SearchRelations\SearchesRelations;


class EcTrack extends Resource {

    use TabsOnEdit, SearchesRelations;

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

    /**
     * The relationship columns that should be searched.
     *
     * @var array
     */
    public static $searchRelations = [
        'author' => ['name', 'email'],
        'taxonomyActivities' => ['name'],
        'taxonomyWheres' => ['name'],
        'taxonomyTargets' => ['name'],
        'taxonomyWhens' => ['name'],
        'taxonomyThemes' => ['name'],
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

        ///////////////////////
        // Index (onlyOnIndex)
        ///////////////////////
        if(NovaCurrentResourceActionHelper::isIndex($request)) {
            return $this->index();
        }

        ///////////////////////
        // Detail (onlyOnDetail)
        ///////////////////////
        if(NovaCurrentResourceActionHelper::isDetail($request)) {
            return $this->detail();
        }

        ////////////////////////////////////////////////////////
        // Form (onlyOnForms,hideWhenCreating,hideWhenUpdating)
        ////////////////////////////////////////////////////////
        if(NovaCurrentResourceActionHelper::isForm($request)) {
            return $this->forms($request);
        }

    }


    private function index() {
        return [

            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name')->sortable(),
            ])->onlyOnIndex(),

            BelongsTo::make('Author', 'author', User::class)->sortable()->onlyOnIndex(),

            DateTime::make(__('Created At'), 'created_at')->sortable()->onlyOnIndex(),

            DateTime::make(__('Updated At'), 'updated_at')->sortable()->onlyOnIndex(),

            Boolean::make(__('OS'), function() {
                $val = false;
                if(!is_null($this->out_source_feature_id)) {
                    $val=true;
                }
                return $val;
            })->onlyOnIndex(),

            Link::make('GJ', 'id')
            ->url(function () {
                return isset($this->id) ? route('api.ec.track.view.geojson', ['id' => $this->id]) : '';
            })
            ->text(__('Open GeoJson'))->icon()->blank()->onlyOnIndex(),
        ];

    }

    private function detail() {
        return [ (new Tabs("EC Track Details: {$this->name} ({$this->id})",[
            'Main' => [
                Text::make('Geohub ID',function (){return $this->id;})->onlyOnDetail(),
                DateTime::make('Created At')->onlyOnDetail(),
                DateTime::make('Updated At')->onlyOnDetail(),
                NovaTabTranslatable::make([
                    Text::make(__('Name'), 'name'),
                    Textarea::make(__('Excerpt'),'excerpt'),
                    Textarea::make('Description'),
                    ])->onlyOnDetail(),
            ],
            'Media' => [
                Text::make('Audio',function () {$this->audio;})->onlyOnDetail(),
                ExternalImage::make(__('Feature Image'), function () {
                    $url = isset($this->model()->featureImage) ? $this->model()->featureImage->url : '';
                    if ('' !== $url && substr($url, 0, 4) !== 'http') {
                        $url = Storage::disk('public')->url($url);
                    }

                    return $url;
                })->withMeta(['width' => 400])->onlyOnDetail(),
                Text::make('Related Url',function () {
                    $out = '';
                    if(is_array($this->related_url) && count($this->related_url)>0){
                        foreach($this->related_url as $label => $url) {
                            $out .= "<a href='{$url}' target='_blank'>{$label}</a></br>";
                        }
                    } else {
                        $out = "No related Url";
                    }
                    return $out;
                })->asHtml(),
            ],
            'Map' => [
                WmEmbedmapsField::make(__('Map'), 'geometry', function () {
                    return [
                        'feature' => $this->getGeojson(),
                    ];
                })->onlyOnDetail(),
            ],
            'Info' => [
                Text::make('Ref'),
                Text::make('From'),
                Text::make('To'),
                Boolean::make('Not Accessible'),
                Textarea::make('Not Accessible Message')->alwaysShow(),
                Text::make('Distance')->onlyOnDetail(),
                Text::make('Duration Forward')->onlyOnDetail(),
                Text::make('Duration Backward')->onlyOnDetail(),
                Text::make('Ascent')->onlyOnDetail(),
                Text::make('Descent')->onlyOnDetail(),
                Text::make('Ele From')->onlyOnDetail(),
                Text::make('Ele To')->onlyOnDetail(),
                Text::make('Ele Max')->onlyOnDetail(),
                Text::make('Ele Min')->onlyOnDetail(),
            ],
            'Scale' => [
                Text::make('Difficulty'),
                Text::make('Cai Scale')
            ],
            'Taxonomies' => [
                Text::make('Activities',function(){
                    if($this->taxonomyActivities()->count() >0) {
                        return implode(',',$this->taxonomyActivities()->pluck('name')->toArray());
                    }
                    return 'No activities';
                }),
                Text::make('Wheres',function(){
                    if($this->taxonomyWheres()->count() >0) {
                        return implode(',',$this->taxonomyWheres()->pluck('name')->toArray());
                    }
                    return 'No Wheres';
                }),
                Text::make('Themes',function(){
                    if($this->taxonomyThemes()->count() >0) {
                        return implode(',',$this->taxonomyThemes()->pluck('name')->toArray());
                    }
                    return 'No Themes';
                }),
                Text::make('Targets',function(){
                    if($this->taxonomyTargets()->count() >0) {
                        return implode(',',$this->taxonomyTargets()->pluck('name')->toArray());
                    }
                    return 'No Targets';
                }),
                Text::make('Whens',function(){
                    if($this->taxonomyWhens()->count() >0) {
                        return implode(',',$this->taxonomyWhens()->pluck('name')->toArray());
                    }
                    return 'No Whens';
                }),
            ],
            'Out Source' => [
                Text::make('Out Source Feature', function() {
                    if(!is_null($this->out_source_feature_id)) {
                        return $this->out_source_feature_id;
                    }
                    else {
                        return 'No Out Source associated';
                    }
                })->onlyOnDetail(),    
            ]
        ]))->withToolbar()];

    }
    private function forms($request) {

        try {
            $geojson = $this->model()->getGeojson();
        } catch (Exception $e) {
            $geojson = [];
        }

        $tab_title = "New EC Track";
        if(NovaCurrentResourceActionHelper::isUpdate($request)) {
            $tab_title = "EC Track Edit: {$this->name} ({$this->id})";
        }

        return [(new Tabs($tab_title,[
            'Main' => [
                NovaTabTranslatable::make([
                    Text::make(__('Name'), 'name'),
                    Textarea::make(__('Excerpt'),'excerpt'),
                    Textarea::make('Description'),
                    ])->onlyOnForms(),
            ],
            'Media' => [

                File::make(__('Audio'), 'audio')->store(function (Request $request, $model) {
                    $file = $request->file('audio');

                    return $model->uploadAudio($file);
                })->acceptedTypes('audio/*')->onlyOnForms(),

                FeatureImagePopup::make(__('Feature Image'), 'featureImage')
                    ->onlyOnForms()
                    ->feature($geojson ?? [])
                    ->apiBaseUrl('/api/ec/track/'),

                EcMediaPopup::make(__('EcMedia'), 'ecMedia')
                    ->onlyOnForms()
                    ->feature($geojson ?? [])
                    ->apiBaseUrl('/api/ec/track/'),
                KeyValue::make('Related Url')
                    ->keyLabel('Label')
                    ->valueLabel('Url with https://')
                    ->actionText('Add new related url')
                    ->rules('json'),
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
                })->onlyOnForms(),
                Ecpoipopup::make(__('EcPoi'))
                    ->nullable()
                    ->onlyOnForms()
                    ->feature($geojson ?? []),
            ],
            'Info' => [
                Text::make('Ref'),
                Text::make('From'),
                Text::make('To'),
                Boolean::make('Not Accessible'),
                Textarea::make('Not Accessible Message')->alwaysShow(),

            ],
            'Scale' => [
                Text::make('Difficulty'),
                Select::make('Cai Scale')->options([
                    'T' => 'Turistico (T)',
                    'E' => 'Escursionistico (E)',
                    'EE' => 'Per escursionisti esperti (EE)',
                    'EEA' => 'Alpinistico (EEA)'
                ]),
            ],
            'Taxonomies' => [
                AttachMany::make('TaxonomyWheres'),
                AttachMany::make('TaxonomyActivities'),
                AttachMany::make('TaxonomyTargets'),
                AttachMany::make('TaxonomyWhens'),
                AttachMany::make('TaxonomyThemes'),
                ],
                
        ]))];

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
        return [
        ];
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
