<?php

namespace App\Console\Commands;

use App\Models\OutSourceTrack;
use App\Providers\OutSourceOSMProvider;
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
    protected $signature = 'geohub:out_source_import 
                            {provider : Select the provider to be used for import (sicai or osm)}';

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
        $provider = $this->argument('provider');
        switch ($provider) {
            case 'sicai':
                $this->handleSicai();
                break;
            case 'osm':
                    $this->handleOSM();
                break;
                
            default:
                Log::error("INVALID PROVIDER");
            break;
        }
    }

    private function handleSicai() {
        Log::info("Handling Sentiero Italia Provider");
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

    private function handleOSM() {
        Log::info("Handling OSM Provider");
        $provider = app(OutSourceOSMProvider::class);
        foreach($this->getHardCodedOSMIds() as $id) {
            Log::info("Processing OSMID $id");
            $item = $provider->getItem($id);
            var_dump($item);
        }
    }

    private function getHardCodedOSMIds() {
        return [
            7744463,
            // 3523766,
            // 2858770,
            // 2858769,
            // 12183019,
            // 12204319,
        ];
    }
}
