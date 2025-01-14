<?php

namespace Laravel\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

trait ResolvesCards
{
    /**
     * Get the cards that are available for the given request.
     *
     * @return \Illuminate\Support\Collection
     */
    public function availableCards(NovaRequest $request)
    {
        return $this->resolveCards($request)->filter->authorize($request)->values();
    }

    /**
     * Get the cards for the given request.
     *
     * @return \Illuminate\Support\Collection
     */
    public function resolveCards(NovaRequest $request)
    {
        return collect(array_values($this->filter($this->cards($request))));
    }

    /**
     * Get the cards available on the entity.
     *
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }
}
