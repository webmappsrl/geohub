<?php

namespace App\Nova;

use App\Helpers\NovaCurrentResourceActionHelper;
use App\Nova\Actions\RegenerateEcMedia;
use Chaseconey\ExternalImage\ExternalImage;
use Eminiarts\Tabs\Tabs;
use Eminiarts\Tabs\TabsOnEdit;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Khalin\Nova\Field\Link;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Panel;
use NovaAttachMany\AttachMany;
use Webmapp\WmEmbedmapsField\WmEmbedmapsField;
use Titasgailius\SearchRelations\SearchesRelations;
use DigitalCreative\MegaFilter\MegaFilter;
use DigitalCreative\MegaFilter\Column;
use DigitalCreative\MegaFilter\HasMegaFilterTrait;
use Laravel\Nova\Fields\Heading;
use PosLifestyle\DateRangeFilter\DateRangeFilter;


class EcMedia extends Resource
{

    use TabsOnEdit, SearchesRelations;
    use HasMegaFilterTrait;


    use TabsOnEdit;
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
        if(NovaCurrentResourceActionHelper::isIndex($request)) {
            return $this->index();
        }

        if(NovaCurrentResourceActionHelper::isDetail($request)) {
            return $this->detail();
        }

        if(NovaCurrentResourceActionHelper::isForm($request)) {
            return $this->form($request);
        }

        
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


    private function index() {
        return [
            ExternalImage::make('Image', function () {
                $thumbnails = $this->model()->thumbnails;
                $url = '';
                if ($thumbnails) {
                    $thumbnails = json_decode($thumbnails,true);
                    if ($thumbnails[array_key_first($thumbnails)]) {
                        $url = $thumbnails[array_key_first($thumbnails)];
                    }
                }
                if (!$url) {
                    $url = $this->model()->url;
                    if (substr($url, 0, 4) !== 'http')
                        $url = Storage::disk('public')->url($url);
                }
                        
                return $url;
            }),

            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name'),
            ]),
            BelongsTo::make('Author', 'author', User::class)->sortable()->hideFromIndex(),
            Date::make(__('Created At'), 'created_at')->sortable()->hideFromIndex(),
            Date::make(__('Updated At'), 'updated_at')->sortable()->hideFromIndex(),
            Text::make('Url', function () {
                $url = $this->model()->url;
                if (substr($url, 0, 4) !== 'http')
                    $url = Storage::disk('public')->url($url);

                return '<a href="' . $url . '" target="_blank">' . __('Original image') . '</a>';
            })->asHtml(),
            Link::make('GeoJSON', 'id')
                ->url(function () {
                    return isset($this->id) ? route('api.ec.media.geojson', ['id' => $this->id]) : '';
                })
                ->text(__('Open GeoJson'))
                ->icon()
                ->blank(),
        ];
    }

    private function detail() {
        return [(new Tabs("Taxnonomy Where Details: {$this->name} ($this->id)",
        [
            'Main' => [
                Text::make('Geohub ID',function(){return $this->id;}),
                BelongsTo::make('Author', 'author', User::class),
                DateTime::make(__('Created At'), 'created_at'),
                DateTime::make(__('Updated At'), 'updated_at'),        
                NovaTabTranslatable::make([
                    Text::make(__('Name'), 'name'),
                    Textarea::make(__('Excerpt'), 'excerpt'),
                    Textarea::make(__('Description'), 'description'),
                ]),     
            ],
            'Images' => $this->getImages(),
            'Map' => [
                WmEmbedmapsField::make(__('Map'), function ($model) {
                    return [
                        'feature' => $model->getGeojson(),
                    ];
                }),    
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
            'Data' => [
                Heading::make($this->getData())->asHtml(),
            ],


        ]
        ))->withToolbar()];
    }
    private function form($request) {

        try {
            $geojson = $this->model()->getGeojson();
        } catch (Exception $e) {
            $geojson = null;
        }


        $tab_title = "New EC Media";
        if(NovaCurrentResourceActionHelper::isUpdate($request)) {
            $tab_title = "EC Media Edit: {$this->name} ({$this->id})";
        }

        return [(new Tabs($tab_title,
        [
            'Main' => [
                NovaTabTranslatable::make([
                    Text::make(__('Name'), 'name'),
                    Textarea::make(__('Excerpt'), 'excerpt'),
                    Textarea::make(__('Description'), 'description'),
                ]),     
            ],
            'Images' => [
                Image::make('Url'),
            ],
            'Map' => [
            ],
            'Taxonomies' => [
                AttachMany::make('TaxonomyActivities'),
                AttachMany::make('TaxonomyTargets'),
                AttachMany::make('TaxonomyWhens'),
                AttachMany::make('TaxonomyThemes'),
                ],


        ]
        ))->withToolbar(),
        new Panel('Map / Geographical info', [
            WmEmbedmapsField::make(__('Map'), 'geometry', function () use ($geojson) {
                return [
                    'feature' => $geojson,
                ];
            }),    
        ]),

    ];
    }


    private function getImages() {
        $return = [];
        $return[] = Text::make('Original Image', function () {
            $url = $this->model()->url;
            if (substr($url, 0, 4) !== 'http')
                $url = Storage::disk('public')->url($url);

            return '<a href="' . $url . '" target="_blank">' . $url .'</a>';
        })->asHtml();

        $return[] =ExternalImage::make('Image', function () {
            $url = $this->model()->url;
            if (substr($url, 0, 4) !== 'http')
                $url = Storage::disk('public')->url($url);

            return $url;
        })->withMeta(['width' => 500]);

        if(isset($this->model()->thumbnails)) {
            $thumbnails = json_decode($this->model()->thumbnails, true);
            $fields = [];
    
            if (isset($thumbnails)) {
                foreach ($thumbnails as $size => $url) {
                    $return[] = Text::make($size, function () use ($url) {
                        return '<a href="' . $url . '" target="_blank">' . $url .'</a>';
                    })->asHtml();                    
                }
            }
        }

        return $return; 
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
        return [
        ];
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
        if ($request->user()->hasRole('Editor')) {
            return [
                new Lenses\MyEcMediasLens(),
            ];
        }
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

        /**
     * This method returns the HTML STRING rendered by DATA tab (object structure and fields)
     * Refers to OFFICIAL DOCUMENTATION:
     * https://docs.google.com/spreadsheets/d/1S5kVk2tBF4ZQxuaeYBLG2lLu8Y8AnfmKzvHft8Pw7ms/edit#gid=0
     *
     * @return string
     */
    public function getData() : string {
        $text = <<<HTML
        <style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}
</style>
<table>
<tr><th>GROUP</th><th>NAME</th><th>TYPE</th><th>NULL</th><th>DEF</th><th>FK</th><th>I18N</th><th>LABEL</th><th>DESCRIPTION</th></tr>
<tr><td><i>main</i></td><td>id</td><td>int8</td><td>NO</td><td>AUTO</td><td>-</td><td>NO</td><td>Geohub ID</td><td>MEDIA identification code in the Geohub</td></tr>
<tr><td><i>main</i></td><td>user_id</td><td>int4</td><td>NO</td><td>NULL</td><td>users</td><td>NO</td><td>Author</td><td>MEDIA author: foreign key wiht table users</td></tr>
<tr><td><i>main</i></td><td>created_at</td><td>timestamp(0)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Created At</td><td>When MEDIA has been created: datetime</td></tr>
<tr><td><i>main</i></td><td>updated_at</td><td>timestamp(0)</td><td>YES</td><td>NULL</td><td>-</td><td>NO</td><td>Updated At</td><td>When MEDIA has been modified last time: datetime</td></tr>
<tr><td><i>main</i></td><td>name</td><td>text</td><td>NO</td><td>NULL</td><td>-</td><td>YES</td><td>Name</td><td>Name of the MEDIA, also know as title</td></tr>
<tr><td><i>main</i></td><td>description</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Description</td><td>Descrption of the MEDIA</td></tr>
<tr><td><i>main</i></td><td>excerpt</td><td>text</td><td>YES</td><td>NULL</td><td>-</td><td>YES</td><td>Excerpt</td><td>Short Description of the MEDIA</td></tr>
<tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>images</i></td><td>url</td><td>varchar(255)</td><td>NO</td><td>NULL</td><td>NULL</td><td>NO</td><td>Original Image</td><td>URL of the original image first updated</td></tr>
<tr><td><i>images</i></td><td>thumbnails</td><td>json</td><td>YES</td><td>NULL</td><td>NULL</td><td>NO</td><td>Thubnails</td><td>List of all thumbnails generated by the GEOMIXER task</td></tr>
<tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>map</i></td><td>geometry</td><td>geography</td><td>YES</td><td>NULL</td><td>NULL</td><td>NO</td><td>Map</td><td>The MEDIA geometry (point)</td></tr>
<tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>
<tr><td><i>outsource</i></td><td>source_id</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>NULL</td><td>NO</td><td>TBD</td><td></td></tr>
<tr><td><i>outsource</i></td><td>import_method</td><td>varchar(255)</td><td>YES</td><td>NULL</td><td>NULL</td><td>NO</td><td>TBD</td><td></td></tr>
<tr><td><i>outsource</i></td><td>source</td><td>text</td><td>YES</td><td>NULL</td><td>NULL</td><td>NO</td><td>TBD</td><td></td></tr>
</table>
HTML;
               return $text;
    }


}
