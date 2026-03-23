<?php

namespace Laravel\Nova\Actions;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Nova\Nova;

trait Actionable
{
    /**
     * Get all of the action events for the user.
     *
     * @return MorphMany
     */
    public function actions()
    {
        return $this->morphMany(Nova::actionEvent(), 'actionable');
    }
}
