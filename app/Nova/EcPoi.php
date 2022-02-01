<?php

namespace App\Nova;

use App\Helpers\NovaCurrentResourceActionHelper;
use App\Nova\Actions\RegenerateEcTrack;
use App\Nova\Filters\EcTracksCaiScaleFilter;
use App\Nova\Metrics\EcTracksMyValue;
use App\Nova\Metrics\EcTracksNewValue;
use App\Nova\Metrics\EcTracksTotalValue;
use Chaseconey\ExternalImage\ExternalImage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\File;
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
use DigitalCreative\MegaFilter\MegaFilter;
use DigitalCreative\MegaFilter\Column;
use DigitalCreative\MegaFilter\HasMegaFilterTrait;
use Laravel\Nova\Fields\Number;
use PosLifestyle\DateRangeFilter\DateRangeFilter;
use Laravel\Nova\Panel;


class EcPoi extends Resource {


    use TabsOnEdit, SearchesRelations;
    use HasMegaFilterTrait;

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
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function fields(Request $request) {

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

            Text::make('Name')->sortable(),

            BelongsTo::make('Author', 'author', User::class)->sortable(),

            DateTime::make(__('Created At'), 'created_at')->sortable(),

            DateTime::make(__('Updated At'), 'updated_at')->sortable(),

            Text::make('Geojson',function () {
                return '<a href="'.route('api.ec.poi.view.geojson', ['id' => $this->id]).'" target="_blank">[x]</a>';
            })->asHtml(),
        ];

    }

    private function detail() {
        return [ (new Tabs("EC Poi Details: {$this->name} ({$this->id})",[
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
                Text::make('Adress / street','addr_street'),
                Text::make('Adress / housenumber','addr_housenumber'),
                Text::make('Adress / postcode','addr_postcode'),
                Text::make('Adress / locality','addr_locality'),
                Text::make('Opening Hours'),
                Text::make('Contact Phone'),
                Text::make('Contact Email'),
                Number::Make('Elevation','ele'),
            ],
            'Taxonomies' => [
                Text::make('Poi Types',function(){
                    if($this->taxonomyPoiTypes()->count() >0) {
                        return implode(',',$this->taxonomyPoiTypes()->pluck('name')->toArray());
                    }
                    return 'No Poi Types';
                }),
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
        ]))->withToolbar()];

    }
    private function forms($request) {

        try {
            $geojson = $this->model()->getGeojson();
        } catch (Exception $e) {
            $geojson = [];
        }

        $tab_title = "New EC Poi";
        if(NovaCurrentResourceActionHelper::isUpdate($request)) {
            $tab_title = "EC Poi Edit: {$this->name} ({$this->id})";
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

                FeatureImagePopup::make(__('Feature Image (by map)'), 'featureImage')
                    ->onlyOnForms()
                    ->feature($geojson ?? [])
                    ->apiBaseUrl('/api/ec/track/'),

                BelongsTo::make('Feature Image (by name)','featureImage',EcMedia::class)
                    ->searchable()
                    ->showCreateRelationButton()
                    ->nullable(),
    
                EcMediaPopup::make(__('Gallery (by map)'), 'ecMedia')
                    ->onlyOnForms()
                    ->feature($geojson ?? [])
                    ->apiBaseUrl('/api/ec/track/'),

                KeyValue::make('Related Url')
                    ->keyLabel('Label')
                    ->valueLabel('Url with https://')
                    ->actionText('Add new related url')
                    ->rules('json'),
            ],

            'Info' => [
                Text::make('Adress / street','addr_street'),
                Text::make('Adress / housenumber','addr_housenumber'),
                Text::make('Adress / postcode','addr_postcode'),
                Text::make('Adress / locality','addr_locality'),
                Text::make('Opening Hours'),
                Text::make('Contact Phone'),
                Text::make('Contact Email'),
                Number::Make('Elevation','ele'),
            ],


            'Taxonomies' => [
                AttachMany::make('TaxonomyPoiTypes'),
                AttachMany::make('TaxonomyWheres'),
                AttachMany::make('TaxonomyActivities'),
                AttachMany::make('TaxonomyTargets'),
                AttachMany::make('TaxonomyWhens'),
                AttachMany::make('TaxonomyThemes'),
                ],
                
            ])),
            new Panel('Map / Geographical info', [
                WmEmbedmapsField::make(__('Map'), 'geometry', function () use ($geojson) {
                    return [
                        'feature' => $geojson,
                    ];
                }),    
            ]),
    
    
    ];

    }


    /**
     * Get the cards available for the request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function cards(Request $request) {
        return [
            MegaFilter::make([
                'columns' => [
                    Column::make('Name')->permanent(),
                    Column::make('Author'),
                    Column::make('Created At'),
                    Column::make('Updated At'),
                    //Column::make('Cai Scale')
                ],
                'filters' => [
                    // https://packagist.org/packages/pos-lifestyle/laravel-nova-date-range-filter
                    (new DateRangeFilter('Created at','created_at')),
                    (new DateRangeFilter('Updated at','updated_at')),

                ],
                'settings' => [

                    /**
                     * Tailwind width classes: w-full w-1/2 w-1/3 w-1/4 etc.
                     */
                    'columnsWidth' => 'w-1/4',
                    'filtersWidth' => 'w-1/3',
                    
                    /**
                     * The default state of the main toggle buttons
                     */
                    'columnsActive' => false,
                    'filtersActive' => false,
                    'actionsActive' => false,
            
                    /**
                     * Show/Hide elements
                     */
                    'showHeader' => true,
                    
                    /**
                     * Labels
                     */
                    'headerLabel' => 'Columns and Filters',
                    'columnsLabel' => 'Columns',
                    'filtersLabel' => 'Filters',
                    'actionsLabel' => 'Actions',
                    'columnsSectionTitle' => 'Additional Columns',
                    'filtersSectionTitle' => 'Filters',
                    'actionsSectionTitle' => 'Actions',
                    'columnsResetLinkTitle' => 'Reset Columns',
                    'filtersResetLinkTitle' => 'Reset Filters',
            
                ],
            ]),

        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function filters(Request $request) {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function lenses(Request $request) {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function actions(Request $request) {
        return [];
    }
}
