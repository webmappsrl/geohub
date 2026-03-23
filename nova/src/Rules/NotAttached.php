<?php

namespace Laravel\Nova\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;

class NotAttached implements Rule
{
    /**
     * The request instance.
     *
     * @var NovaRequest
     */
    public $request;

    /**
     * The model instance.
     *
     * @var Model
     */
    public $model;

    /**
     * Create a new rule instance.
     *
     * @param  Model  $model
     * @return void
     */
    public function __construct(NovaRequest $request, $model)
    {
        $this->model = $model;
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
        return ! in_array(
            $this->request->input($this->request->relatedResource),
            $this->model->{$this->request->viaRelationship}()
                ->withoutGlobalScopes()->get()->modelKeys()
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('nova::validation.attached');
    }
}
