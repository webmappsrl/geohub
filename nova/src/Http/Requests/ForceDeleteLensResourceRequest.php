<?php

namespace Laravel\Nova\Http\Requests;

use Closure;
use Illuminate\Support\Collection;

class ForceDeleteLensResourceRequest extends LensResourceDeletionRequest
{
    /**
     * Get the selected models for the action in chunks.
     *
     * @param  int  $count
     * @return mixed
     */
    public function chunks($count, Closure $callback)
    {
        return $this->chunkWithAuthorization($count, $callback, function ($models) {
            return $this->deletableModels($models);
        });
    }

    /**
     * Get the models that may be deleted.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function deletableModels(Collection $models)
    {
        return $models->mapInto($this->resource())
            ->filter
            ->authorizedToForceDelete($this)
            ->map->model();
    }
}
