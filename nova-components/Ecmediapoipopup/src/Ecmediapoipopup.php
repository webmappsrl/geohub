<?php

namespace Webmapp\Ecmediapoipopup;

use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;

class Ecmediapoipopup extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'ecmediapoipopup';


    public function __construct($name, $attribute = null, $resource = null)
    {
        parent::__construct($name, $attribute);

        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);

        $this->resource = $resource;

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->manyToManyRelationship = $this->attribute;


        $this->fillUsing(function ($request, $model, $attribute, $requestAttribute) use ($resource) {
            if (is_subclass_of($model, 'Illuminate\Database\Eloquent\Model')) {
                $model::saved(function ($model) use ($attribute, $request) {

                    // fetch the submitted values
                    $values = json_decode(request()->input($attribute), true);

                    // if $values is null make it an empty array instead
                    if (is_null($values)) {
                        $values = [];
                    }

                    // remove `null` values that may be submitted
                    $filtered_values = array_filter($values);

                    // sync

                    $changes = $model->$attribute()->sync($filtered_values);
                    
                    $method = Str::camel($attribute) . 'Synced';

                    $parent = $request->newResource();

                    if (method_exists($parent, $method)) {
                        $parent->{$method}($changes);
                    }
                });

                // prevent relationship json on parent resource:

                $request->replace(
                    $request->except($attribute)
                );
            }
        });
    }

    public function feature(array $geojson)
    {
        return $this->withMeta(['geojson' => $geojson]);
    }
}
