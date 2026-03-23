<?php

namespace Laravel\Nova\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\MorphOne;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;

class Relatable implements Rule
{
    /**
     * The request instance.
     *
     * @var NovaRequest
     */
    public $request;

    /**
     * The query builder instance.
     *
     * @var Builder
     */
    public $query;

    /**
     * Create a new rule instance.
     *
     * @param  Builder  $query
     * @return void
     */
    public function __construct(NovaRequest $request, $query)
    {
        $this->query = $query;
        $this->request = $request;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $model = $this->query->tap(function ($query) {
            tap($query->getQuery(), function ($builder) {
                $builder->orders = [];

                $builder->select(
                    ! empty($builder->joins) ? $builder->from.'.*' : '*'
                );
            });
        })->whereKey($value)->first();

        if (! $model) {
            return false;
        }

        if ($this->relationshipIsFull($model, $attribute, $value)) {
            return false;
        }

        if ($resource = Nova::resourceForModel($model)) {
            return $this->authorize($resource, $model);
        }

        return true;
    }

    /**
     * Determine if the relationship is "full".
     *
     * @param  Model  $model
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    protected function relationshipIsFull($model, $attribute, $value)
    {
        $inverseRelation = $this->request->newResource()
            ->resolveInverseFieldsForAttribute($this->request, $attribute)->first(function ($field) {
                return $field instanceof HasOne || $field instanceof MorphOne;
            });

        if ($inverseRelation && $this->request->resourceId) {
            $modelBeingUpdated = $this->request->findModelOrFail();

            if (is_null($modelBeingUpdated->{$attribute})) {
                return false;
            }

            if ($modelBeingUpdated->{$attribute}->getKey() == $value) {
                return false;
            }
        }

        return $inverseRelation &&
               $model->{$inverseRelation->attribute}()->count() > 0;
    }

    /**
     * Authorize that the user is allowed to relate this resource.
     *
     * @param  string  $resource
     * @param  Model  $model
     * @return bool
     */
    protected function authorize($resource, $model)
    {
        return (new $resource($model))->authorizedToAdd(
            $this->request, $this->request->model()
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('nova::validation.relatable');
    }
}
