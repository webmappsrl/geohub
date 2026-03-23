<?php

namespace Laravel\Nova\Query;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Nova\TrashedStatus;

class ApplySoftDeleteConstraint
{
    /**
     * Apply the trashed state constraint to the query.
     *
     * @param  Builder  $query
     * @param  string  $withTrashed
     * @return Builder
     */
    public function __invoke($query, $withTrashed)
    {
        if ($withTrashed == TrashedStatus::WITH) {
            $query = $query->withTrashed();
        } elseif ($withTrashed == TrashedStatus::ONLY) {
            $query = $query->onlyTrashed();
        }

        return $query;
    }
}
