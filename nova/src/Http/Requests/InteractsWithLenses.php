<?php

namespace Laravel\Nova\Http\Requests;

use Illuminate\Support\Collection;
use Laravel\Nova\Lenses\Lens;

trait InteractsWithLenses
{
    /**
     * Get the lens instance for the given request.
     *
     * @return Lens
     */
    public function lens()
    {
        return $this->availableLenses()->first(function ($lens) {
            return $this->lens === $lens->uriKey();
        }) ?: abort($this->lensExists() ? 403 : 404);
    }

    /**
     * Get all of the possible lenses for the request.
     *
     * @return Collection
     */
    public function availableLenses()
    {
        return $this->newResource()->availableLenses($this);
    }

    /**
     * Determine if the specified action exists at all.
     *
     * @return bool
     */
    protected function lensExists()
    {
        return $this->newResource()->resolveLenses($this)->contains(function ($lens) {
            return $this->lens === $lens->uriKey();
        });
    }
}
