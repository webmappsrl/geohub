<?php

namespace Laravel\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Query\ApplySoftDeleteConstraint;

trait PerformsQueries
{
    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $search
     * @param  string  $withTrashed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function buildIndexQuery(NovaRequest $request, $query, $search = null,
        array $filters = [], array $orderings = [],
        $withTrashed = TrashedStatus::DEFAULT)
    {
        return static::applyOrderings(static::applyFilters(
            $request, static::initializeQuery($request, $query, (string) $search, $withTrashed), $filters
        ), $orderings)->tap(function ($query) use ($request) {
            static::indexQuery($request, $query->with(static::$with));
        });
    }

    /**
     * Initialize the given index query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @param  string  $withTrashed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function initializeQuery(NovaRequest $request, $query, $search, $withTrashed)
    {
        if (empty(trim($search))) {
            return static::applySoftDeleteConstraint($query, $withTrashed);
        }

        return static::usesScout()
                ? static::initializeQueryUsingScout($request, $query, $search, $withTrashed)
                : static::applySearch(static::applySoftDeleteConstraint($query, $withTrashed), $search);
    }

    /**
     * Apply the search query to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applySearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $model = $query->getModel();

            $connectionType = $model->getConnection()->getDriverName();

            $canSearchPrimaryKey = ctype_digit($search) &&
                                   in_array($model->getKeyType(), ['int', 'integer']) &&
                                   ($connectionType != 'pgsql' || $search <= static::maxPrimaryKeySize()) &&
                                   in_array($model->getKeyName(), static::$search);

            if ($canSearchPrimaryKey) {
                $query->orWhere($model->getQualifiedKeyName(), $search);
            }

            $likeOperator = $connectionType == 'pgsql' ? 'ilike' : 'like';

            foreach (static::searchableColumns() as $column) {
                $query->orWhere(
                    $model->qualifyColumn($column),
                    $likeOperator,
                    static::searchableKeyword($column, $search)
                );
            }
        });
    }

    /**
     * Initialize the given index query using Laravel Scout.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @param  string  $withTrashed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function initializeQueryUsingScout(NovaRequest $request, $query, $search, $withTrashed)
    {
        $keys = static::buildIndexQueryUsingScout($request, $search, $withTrashed)->get()->map->getKey();

        return static::applySoftDeleteConstraint(
            $query->whereIn(static::newModel()->getQualifiedKeyName(), $keys->all()), $withTrashed
        );
    }

    /**
     * Build an "index" result for the given resource using Scout.
     *
     * @param  string|null  $search
     * @param  string  $withTrashed
     * @return \Laravel\Scout\Builder
     */
    public static function buildIndexQueryUsingScout(NovaRequest $request, $search = null,
        $withTrashed = TrashedStatus::DEFAULT)
    {
        return tap(static::applySoftDeleteConstraint(
            static::newModel()->search($search), $withTrashed
        ), function ($scoutBuilder) use ($request) {
            static::scoutQuery($request, $scoutBuilder);
        })->take(static::$scoutSearchResults);
    }

    /**
     * Scope the given query for the soft delete state.
     *
     * @param  mixed  $query
     * @param  string  $withTrashed
     * @return mixed
     */
    protected static function applySoftDeleteConstraint($query, $withTrashed)
    {
        return static::softDeletes()
                ? (new ApplySoftDeleteConstraint)->__invoke($query, $withTrashed)
                : $query;
    }

    /**
     * Apply any applicable filters to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applyFilters(NovaRequest $request, $query, array $filters)
    {
        collect($filters)->each->__invoke($request, $query);

        return $query;
    }

    /**
     * Apply any applicable orderings to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applyOrderings($query, array $orderings)
    {
        $orderings = array_filter($orderings);

        if (empty($orderings)) {
            return empty($query->getQuery()->orders) && ! static::usesScout()
                        ? $query->latest($query->getModel()->getQualifiedKeyName())
                        : $query;
        }

        foreach ($orderings as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build a Scout search query for the given resource.
     *
     * @param  \Laravel\Scout\Builder  $query
     * @return \Laravel\Scout\Builder
     */
    public static function scoutQuery(NovaRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build a "detail" query for the given resource.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function detailQuery(NovaRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableQuery(NovaRequest $request, $query)
    {
        return $query;
    }
}
