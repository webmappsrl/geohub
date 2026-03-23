<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Support\Collection;

class LensCardRequest extends CardRequest
{
    use InteractsWithLenses;

    /**
     * Get all of the possible metrics for the request.
     *
     * @return Collection
     */
    public function availableCards()
    {
        return $this->lens()->availableCards($this);
    }
}
