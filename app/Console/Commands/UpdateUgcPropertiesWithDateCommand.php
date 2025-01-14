<?php

namespace App\Console\Commands;

use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use Illuminate\Console\Command;

class UpdateUgcPropertiesWithDateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:update-ugc-properties-with-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the UGC properties with the createdAt and updatedAt dates';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command to update UGC properties with dates.
     *
     * This method retrieves all instances of UgcTrack, UgcPoi, and UgcMedia,
     * and updates their properties with the createdAt and updatedAt dates if not already set.
     *
     * @return int Returns 0 on successful execution.
     */
    public function handle()
    {
        $this->updateProperties(UgcTrack::all());
        $this->updateProperties(UgcPoi::all());
        $this->updateProperties(UgcMedia::all());

        return 0;
    }

    /**
     * Update properties with createdAt and updatedAt dates.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $items
     * @return void
     */
    private function updateProperties($items)
    {
        foreach ($items as $item) {
            $properties = $item->properties;

            if (! isset($properties['createdAt'])) {
                $properties['createdAt'] = $item->created_at;
                $properties['updatedAt'] = $item->created_at;
                $item->properties = $properties;
                $item->timestamps = false;
                $item->saveQuietly();
            }
        }
    }
}
