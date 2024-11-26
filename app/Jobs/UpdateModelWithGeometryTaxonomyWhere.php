<?php

namespace App\Jobs;

use App\Models\EcTrack;
use App\Models\TaxonomyWhere;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateModelWithGeometryTaxonomyWhere implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $model;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Model $model)
    {
        //TODO: add validation about geometry attribute existence
        //TODO: add validation about where taxonomy relation existence
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

        if (!empty($ids)) {
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
        $ids = TaxonomyWhere::whereRaw(
            'public.ST_Intersects('
                . 'public.ST_Force2D('
                . "(SELECT geometry from {$this->model->getTable()} where id = {$this->model->id})"
                . "::geometry)"
                . ', geometry)'
        )->get()->pluck('id')->toArray();

        return $ids;
    }
}
