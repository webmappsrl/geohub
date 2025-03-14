<?php

namespace App\Jobs;

use App\Models\TaxonomyWhere;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateModelWithGeometryTaxonomyWhere implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 300;

    protected $model;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Model $model)
    {
        // TODO: add validation about geometry attribute existence
        // TODO: add validation about where taxonomy relation existence
        $this->model = $model;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $ids = $this->associateWhere();

        if (! empty($ids)) {
            $this->model->taxonomyWheres()->sync($ids);
        }
    }

    /**
     * Imported from geomixer
     *
     * @return array the ids of associate Wheres
     */
    public function associateWhere()
    {

        $table = $this->model->getTable();
        $geom = DB::table($table)->selectRaw('ST_Force2D(geometry::geometry) as geometry')->where('id', $this->model->id)->value('geometry');

        if (! $geom) {
            return [];
        }

        $ids = TaxonomyWhere::whereRaw('geometry && ?', [$geom])
            ->whereRaw('ST_Intersects(?, geometry)', [$geom])
            ->pluck('id')
            ->toArray();

        return $ids;
    }
}
