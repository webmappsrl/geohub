<?php

namespace Laravel\Nova\Http\Controllers;

use Laravel\Nova\Http\Requests\NovaRequest;

trait HandlesCustomRelationKeys
{
    /**
     * Determine if the user has set a custom relation key for the field.
     *
     * @return bool
     */
    protected function usingCustomRelationKey(NovaRequest $request)
    {
        return $request->relatedResource !== $request->viaRelationship;
    }

    /**
     * Get the rule key used for fetching the field's validation rules.
     *
     * @return mixed
     */
    protected function getRuleKey(NovaRequest $request)
    {
        return $this->usingCustomRelationKey($request)
            ? $request->viaRelationship
            : $request->relatedResource;
    }

    /**
     * Get the custom field attributes names for validation.
     *
     * @param  string  $attribute
     * @return array
     */
    protected function customRulesKeys(NovaRequest $request, $attribute)
    {
        return [$this->getRuleKey($request) => $attribute];
    }
}
