<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class TaxonomyBulkMergeService
{
    public function merge(
        Collection $models,
        int $mainId,
        string $pivotTable,
        string $foreignKey,
        string $morphIdColumn,
        string $morphTypeColumn
    ): void {
        if ($models->count() < 2) {
            throw new InvalidArgumentException('Select at least two taxonomy terms to merge.');
        }

        if (! $models->contains(fn ($model) => (int) $model->id === $mainId)) {
            throw new InvalidArgumentException("Main taxonomy id {$mainId} is not among the selected models.");
        }

        if (! Schema::hasTable($pivotTable)) {
            throw new InvalidArgumentException("Pivot table \"{$pivotTable}\" does not exist.");
        }

        foreach ([$foreignKey, $morphIdColumn, $morphTypeColumn] as $column) {
            if (! Schema::hasColumn($pivotTable, $column)) {
                throw new InvalidArgumentException("Pivot table \"{$pivotTable}\" has no column \"{$column}\".");
            }
        }

        $duplicates = $models->filter(fn ($model) => (int) $model->id !== $mainId);

        DB::transaction(function () use ($duplicates, $mainId, $pivotTable, $foreignKey, $morphIdColumn, $morphTypeColumn) {
            foreach ($duplicates as $duplicate) {
                $duplicateId = (int) $duplicate->id;

                DB::table($pivotTable)
                    ->where($foreignKey, $duplicateId)
                    ->whereRaw(
                        "({$morphIdColumn}, {$morphTypeColumn}) IN (SELECT {$morphIdColumn}, {$morphTypeColumn} FROM {$pivotTable} WHERE {$foreignKey} = ?)",
                        [$mainId]
                    )
                    ->delete();

                DB::table($pivotTable)
                    ->where($foreignKey, $duplicateId)
                    ->update([$foreignKey => $mainId]);

                $duplicate->delete();
            }
        });
    }
}
