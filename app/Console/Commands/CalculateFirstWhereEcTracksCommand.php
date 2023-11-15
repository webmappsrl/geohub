<?php

namespace App\Console\Commands;

use App\Models\EcPoi;
use App\Models\EcTrack;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateFirstWhereEcTracksCommand extends Command
{
    protected $author_id;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:calculate_first_where 
                            {type : Set the Ec type (track, poi)}
                            {author : Set the author that must be assigned to EcFeature created, use email or ID }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates the first where of the Ectracks based on the Kilometers of each track on Admin level 8';

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
                $risultato = [];
                $wheres = $feature->taxonomyWheres;
                $track_geom = $feature->geometry;
                foreach ($wheres as $where) {
                    if ($where->admin_level == 8) {
                        $where_geom = $where->geometry;
                        $risultato[$where->id] = DB::select("SELECT ST_Length(ST_Intersection(:track_geom, :where_geom)::geography) / 1000 AS km", ['track_geom' => $track_geom,'where_geom' => $where_geom])[0]->km;
                    }
                }
                if (!empty($risultato)) {
                    $feature->taxonomy_wheres_show_first = $this->trovaChiaveValoreMassimo($risultato);
                    Log::info('Adding First where tax to ectrack: ' . $feature->id);
                    $feature->save();
                }
            }
        } catch (Exception $e) {
            throw new Exception('Error ' . $feature->id . ' ERROR ' . $e->getMessage());
        }

    }

    public function trovaChiaveValoreMassimo($array)
    {
        $valoreMassimo = max($array);
        $chiaveValoreMassimo = array_search($valoreMassimo, $array);

        return $chiaveValoreMassimo;
    }
}
