<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\EcPoi;
use App\Models\UgcMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GetRankedUsersNearPoisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:get_ranked_users_near_pois {--app_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get ranked users near pois';

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
        if ($this->option('app_id')) {
            $app = App::where('id', $this->option('app_id'))->first();
            if (!$app) {
                $this->error('App with id ' . $this->option('app_id') . ' not found!');
                return;
            }
            if (!$app->app_id) {
                $this->error('This app does not have app_id! Please add app_id. (e.g. it.webmapp.webmapp)');
                return;
            }

            $rankings = EcPoi::query()
            ->select('ugc_media.user_id', 
                    DB::raw('string_agg(DISTINCT CAST(ugc_media.id as TEXT), \',\') as media_ids'), 
                    DB::raw('COUNT(DISTINCT ugc_media.user_id) as unique_media_count'),
                    'ec_pois.id')
            ->join('ugc_media', function ($join) use ($app) {
                $join->on('ec_pois.user_id', '=', DB::raw("'".$app->user_id."'"))
                    ->whereRaw("ST_DWithin(ugc_media.geometry, ec_pois.geometry, 100.0)")
                    ->where('ugc_media.app_id', '=', DB::raw("'".$app->app_id."'"));
            })
            ->groupBy('ugc_media.user_id','ec_pois.id')
            ->orderByDesc('unique_media_count')
            ->get();

            $groupedArray = [];
            foreach ($rankings as $item) {
                $userId = $item['user_id'];
                $id = $item['id'];
                $mediaIds = $item['media_ids'];
            
                // Initialize the user_id array if not already set
                if (!isset($groupedArray[$userId])) {
                    $groupedArray[$userId] = [];
                }
            
                // Append the id:media_ids pair to the user_id key
                $groupedArray[$userId][] = [$id => $mediaIds];
            }
            
            $app->classification = $groupedArray;
            $app->save();
            return;
        }
        $this->error('app_id not found! Please provide app_id as an option. (e.g. --app_id=1)');
        return;
    }
}
