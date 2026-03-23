<?php

namespace Laravel\Nova\Metrics;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class Partition extends Metric
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'partition-metric';

    /**
     * Rounding precision.
     *
     * @var int
     */
    public $roundingPrecision = 0;

    /**
     * Rounding mode.
     *
     * @var int
     */
    public $roundingMode = PHP_ROUND_HALF_UP;

    /**
     * Return a partition result showing the segments of a count aggregate.
     *
     * @param  Request  $request
     * @param  Builder|string  $model
     * @param  string  $groupBy
     * @param  Expression|string|null  $column
     * @return PartitionResult
     */
    public function count($request, $model, $groupBy, $column = null)
    {
        return $this->aggregate($request, $model, 'count', $column, $groupBy);
    }

    /**
     * Return a partition result showing the segments of an average aggregate.
     *
     * @param  Request  $request
     * @param  Builder|string  $model
     * @param  Expression|string|null  $column
     * @param  string  $groupBy
     * @return PartitionResult
     */
    public function average($request, $model, $column, $groupBy)
    {
        return $this->aggregate($request, $model, 'avg', $column, $groupBy);
    }

    /**
     * Return a partition result showing the segments of a sum aggregate.
     *
     * @param  Request  $request
     * @param  Builder|string  $model
     * @param  Expression|string|null  $column
     * @param  string  $groupBy
     * @return PartitionResult
     */
    public function sum($request, $model, $column, $groupBy)
    {
        return $this->aggregate($request, $model, 'sum', $column, $groupBy);
    }

    /**
     * Return a partition result showing the segments of a max aggregate.
     *
     * @param  Request  $request
     * @param  Builder|string  $model
     * @param  Expression|string|null  $column
     * @param  string  $groupBy
     * @return PartitionResult
     */
    public function max($request, $model, $column, $groupBy)
    {
        return $this->aggregate($request, $model, 'max', $column, $groupBy);
    }

    /**
     * Return a partition result showing the segments of a min aggregate.
     *
     * @param  Request  $request
     * @param  Builder|string  $model
     * @param  Expression|string|null  $column
     * @param  string  $groupBy
     * @return PartitionResult
     */
    public function min($request, $model, $column, $groupBy)
    {
        return $this->aggregate($request, $model, 'min', $column, $groupBy);
    }

    /**
     * Return a partition result showing the segments of a aggregate.
     *
     * @param  Request  $request
     * @param  Builder|string  $model
     * @param  string  $function
     * @param  Expression|string|null  $column
     * @param  string  $groupBy
     * @return PartitionResult
     */
    protected function aggregate($request, $model, $function, $column, $groupBy)
    {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        $wrappedColumn = $column instanceof Expression
                ? (string) $column
                : $query->getQuery()->getGrammar()->wrap(
                    $column ?? $query->getModel()->getQualifiedKeyName()
                );

        $results = $query->select(
            $groupBy, DB::raw("{$function}({$wrappedColumn}) as aggregate")
        )->groupBy($groupBy)->get();

        return $this->result($results->mapWithKeys(function ($result) use ($groupBy) {
            return $this->formatAggregateResult($result, $groupBy);
        })->all());
    }

    /**
     * Format the aggregate result for the partition.
     *
     * @param  Model  $result
     * @param  string  $groupBy
     * @return array
     */
    protected function formatAggregateResult($result, $groupBy)
    {
        $key = with($result->{last(explode('.', $groupBy))}, function ($key) {
            if ($key instanceof BackedEnum) {
                return $key->value;
            }

            return $key;
        });

        return [$key => $result->aggregate];
    }

    /**
     * Create a new partition metric result.
     *
     * @return PartitionResult
     */
    public function result(array $value)
    {
        return new PartitionResult(collect($value)->map(function ($number) {
            return round($number, $this->roundingPrecision, $this->roundingMode);
        })->toArray());
    }
}
