<?php

namespace App\Console\Commands;

use App\Models\OutSourceTrack;
use App\Providers\OutSourceSentieroItaliaProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutSourceImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:out_source_import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from SICAI';

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
        $si = app(OutSourceSentieroItaliaProvider::class);
        foreach ($si->getTrackList() as $id) {
            Log::info("Importing track: source_id -> {$id}");
            $os = OutSourceTrack::firstOrCreate([
                'provider' => 'App\Providers\OutSourceSentieroItaliaProvider',
                'type' => 'track',
                'source_id' => $id,
            ]);
            $item = $si->getItem($id);
            $os->tags=$item['tags'];
            $os->geometry=DB::raw("(ST_GeomFromGeoJSON('{$item['geometry']}'))");
            $os->save();
        }
        return 0;
    }
}
