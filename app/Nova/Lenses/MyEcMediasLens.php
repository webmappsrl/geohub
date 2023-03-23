<?php

namespace App\Nova\Lenses;

use App\Models\EcMedia;
use App\Models\User;
use Chaseconey\ExternalImage\ExternalImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Lenses\Lens;

class MyEcMediasLens extends Lens
{
    /**
     * Get the query builder / paginator for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        $user = $request->user();

        return $request->withOrdering($request->withFilters(
            $query->where('user_id', $user->id)
        ))->orderBy('id', 'desc');
    }

    /**
     * Get the fields available to the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            ExternalImage::make('Image', function () {
                $thumbnails = $this->thumbnails;
                $url = '';
                if ($thumbnails) {
                    $thumbnails = json_decode($thumbnails,true);
                    if ($thumbnails[array_key_first($thumbnails)]) {
                        $url = $thumbnails[array_key_first($thumbnails)];
                    }
                }
                if (!$url) {
                    $url = $this->url;
                    if (substr($url, 0, 4) !== 'http')
                        $url = Storage::disk('public')->url($url);
                }
                        
                return $url;
            }),

            NovaTabTranslatable::make([
                Text::make(__('Name'), 'name'),
            ]),
            // BelongsTo::make('Author', 'author', User::class)->sortable(),
            Text::make('Author', function (){
                return $this->author->name;
            }),
            Text::make('Url', function () {
                $url = $this->url;
                if (substr($url, 0, 4) !== 'http')
                    $url = Storage::disk('public')->url($url);

                return '<a href="' . $url . '" target="_blank">' . __('Original image') . '</a>';
            })->asHtml()
        ];
    }

    /**
     * Get the cards available on the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available on the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return parent::actions($request);
    }

    /**
     * Get the URI key for the lens.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'my-ec-medias-lens';
    }
    
    public function name()
    {
        return 'Le mie foto';
    }
}
