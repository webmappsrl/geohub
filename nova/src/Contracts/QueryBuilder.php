<?php

namespace Laravel\Nova\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\TrashedStatus;

interface QueryBuilder
{
    /**
     * Build a "whereKey" query for the given resource.
     *
     * @param  Builder  $query
     * @param  string  $key
     */
    public function whereKey($query, $key);

    /**
     * Build a "search" query for the given resource.
     *
     * @param  Builder  $query
     * @param  string|null  $search
     * @param  string  $withTrashed
     * @return $this
     */
    public function search(NovaRequest $request, $query, $search = null,
        array $filters = [], array $orderings = [],
        $withTrashed = TrashedStatus::DEFAULT);

    /**
     * Set the "limit" for the search query.
     *
     * @param  int  $limit
     * @return $this
     */
    public function limit($limit);

    /**
     * Get the results of the search.
     *
     * @return Collection
     */
    public function get();

    /**
     * Get a lazy collection for the given query.
     *
     * @return LazyCollection
     */
    public function cursor();

    /**
     * Get the paginated results of the query.
     *
     * @param  int  $perPage
     * @return array
     */
    public function paginate($perPage);

    /**
     * Convert the query builder to an Eloquent query builder (skip using Scout).
     *
     * @return Builder
     */
    public function toBase();

    /**
     * Convert the query builder to an fluent query builder (skip using Scout).
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function toBaseQueryBuilder();
}
