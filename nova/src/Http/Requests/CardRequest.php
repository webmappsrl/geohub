<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Support\Collection;

class CardRequest extends NovaRequest
{
    /**
     * Get all of the possible metrics for the request.
     *
     * @return Collection
     */
    public function availableCards()
    {
        return $this->newResource()->availableCards($this);
    }
}
