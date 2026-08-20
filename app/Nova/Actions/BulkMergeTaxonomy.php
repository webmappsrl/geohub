<?php

namespace App\Nova\Actions;

use App\Services\TaxonomyBulkMergeService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\Select;

class BulkMergeTaxonomy extends Action
{
    use InteractsWithQueue, Queueable;

    protected string $modelClass;

    protected string $pivotTable;

    protected string $foreignKey;

    protected string $morphIdColumn;

    protected string $morphTypeColumn;

    protected string $actionName;

    public function __construct(
        string $modelClass,
        string $pivotTable,
        string $foreignKey,
        string $morphIdColumn,
        string $morphTypeColumn,
        string $actionName
    ) {
        $this->modelClass = $modelClass;
        $this->pivotTable = $pivotTable;
        $this->foreignKey = $foreignKey;
        $this->morphIdColumn = $morphIdColumn;
        $this->morphTypeColumn = $morphTypeColumn;
        $this->actionName = $actionName;

        $this->confirmText(
            'This merge is irreversible. Pivot associations move to the Main term; non-Main selected terms are permanently deleted. The Main must be one of the terms you selected for this action. Metadata of deleted terms is not merged.'
        );
    }

    public function name()
    {
        return $this->actionName;
    }

    public function handle(ActionFields $fields, Collection $models)
    {
        $mainId = (int) $fields->get('main_taxonomy');

        try {
            app(TaxonomyBulkMergeService::class)->merge(
                $models,
                $mainId,
                $this->pivotTable,
                $this->foreignKey,
                $this->morphIdColumn,
                $this->morphTypeColumn
            );
        } catch (\InvalidArgumentException $e) {
            return Action::danger($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('BulkMergeTaxonomy merge failed', [
                'model' => $this->modelClass,
                'pivot_table' => $this->pivotTable,
                'main_id' => $mainId,
                'exception' => $e,
            ]);

            return Action::danger('Error while merging: '.$e->getMessage());
        }

        return Action::message('Merge completed successfully.');
    }

    public function fields()
    {
        $options = $this->modelClass::query()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function ($model) {
                $name = is_array($model->name) && isset($model->name['it'])
                    ? $model->name['it']
                    : (is_string($model->name) ? $model->name : json_encode($model->name));

                return [$model->id => "{$name} ({$model->identifier}) [#{$model->id}]"];
            })->toArray();

        return [
            Heading::make(
                '<p><strong>Irreversible.</strong> Choose the Main term from the list below; '
                .'it must be one of the terms you selected for this merge. '
                .'Non-Main selected terms will be permanently deleted.</p>'
            )->asHtml(),
            Select::make('Main taxonomy', 'main_taxonomy')
                ->options($options)
                ->displayUsingLabels()
                ->searchable()
                ->rules('required'),
        ];
    }
}
