<?php

namespace Laravel\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;

trait ResolvesLenses
{
    /**
     * Get the lenses that are available for the given request.
     *
     * @return Collection
     */
    public function availableLenses(NovaRequest $request)
    {
        return $this->resolveLenses($request)->filter->authorizedToSee($request)->values();
    }

    /**
     * Get the lenses for the given request.
     *
     * @return Collection
     */
    public function resolveLenses(NovaRequest $request)
    {
        return collect(array_values($this->filter($this->lenses($request))));
    }

    /**
     * Get the lenses available on the resource.
     *
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }
}
