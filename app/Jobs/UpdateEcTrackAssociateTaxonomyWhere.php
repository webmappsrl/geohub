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
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateEcTrackAssociateTaxonomyWhere implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $ecTrack;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(EcTrack $ecTrack)
    {
        $this->ecTrack = $ecTrack;
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
            $this->ecTrack->taxonomyWheres()->sync($ids);
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
                . "(SELECT geometry from ec_tracks where id = {$this->ecTrack->id})"
                . ")"
                . ', geometry)'
        )->get()->pluck('id')->toArray();

        return $ids;
    }
}
