<?php

namespace App\Console\Commands;

use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CopyFeatureToGalleryCommand extends Command
{
    protected $author_id;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:feature_to_gallery 
                            {type : Set the Ec type (track, poi)}
                            {author : Set the author that must be assigned to EcFeature created, use email or ID }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copies the feature image of the feature to the first ficture of the gallery it the gallery is empty.';

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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');
        $author = $this->argument('author');

        // get All Ec Features
        switch ($type) {
            case "track":
                $eloquentQuery = EcTrack::query();
                break;
            case "poi":
                $eloquentQuery = EcPoi::query();
                break;
            default:
                break;
        }

        if (is_numeric($author)) {
            try {
                $user = User::find(intval($author));
                $this->author_id = $user->id;
            } catch (Exception $e) {
                throw new Exception('No User found with this ID ' . $author);
            }
        } else {
            try {
                $user = User::where('email', strtolower($author))->first();

                $this->author_id = $user->id;

            } catch (Exception $e) {
                throw new Exception('No User found with this email ' . $author);
            }
        }

        try {
            $features = $eloquentQuery->where('user_id', $this->author_id)->get();

            foreach ($features as $feature) {
                if ($feature->ecMedia()->count() < 1) {
                    if ($feature->feature_image) {
                        Log::info('Updating: ' . $feature->id);
                        $feature->ecMedia()->sync($feature->featureImage);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('EC image gallery not updated ' . $feature->id . ' ERROR ' . $e->getMessage());
        }

    }
}
