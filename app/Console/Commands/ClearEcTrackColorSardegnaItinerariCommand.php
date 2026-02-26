<?php

namespace App\Console\Commands;

use App\Models\EcTrack;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearEcTrackColorSardegnaItinerariCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:clear-ec-track-color-sardegna-itinerari
                            {author : Author (email or ID) of tracks to update, e.g. sardegnasentieri@webmapp.it}
                            {--dry-run : List tracks that would be updated without modifying the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear color from Sardegna itinerari EcTracks (theme sardegnas-itinerario) for the given author';

    protected Logger $logChannel;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->logChannel = Log::channel(config('out_source_logging.default_channel', 'stack'));
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $author = $this->argument('author');
        $dryRun = $this->option('dry-run');

        $authorId = $this->resolveAuthorId($author);
        if ($authorId === null) {
            $this->error("No user found for author: {$author}");

            return Command::FAILURE;
        }

        try {
            $query = EcTrack::where('user_id', $authorId)
                ->whereHas('taxonomyThemes', function ($q) {
                    $q->where('identifier', 'sardegnas-itinerario');
                })
                ->where(function ($q) {
                    $q->whereNotNull('color')->orWhere('color', '!=', '');
                });

            $tracks = $query->get();
        } catch (\Exception $e) {
            Log::error('ClearEcTrackColorSardegnaItinerariCommand: error querying tracks: '.$e->getMessage());
            $this->error('Error querying tracks: '.$e->getMessage());

            return Command::FAILURE;
        }

        $count = $tracks->count();

        if ($count === 0) {
            $this->info('No Sardegna itinerari tracks with color to clear.');

            return Command::SUCCESS;
        }

        $ids = $tracks->pluck('id')->toArray();

        if ($dryRun) {
            $this->info('[DRY-RUN] Would clear color for '.$count.' track(s) (ID: '.implode(', ', $ids).')');

            return Command::SUCCESS;
        }

        try {
            DB::table('ec_tracks')->whereIn('id', $ids)->update(['color' => null]);
        } catch (\Exception $e) {
            Log::error('ClearEcTrackColorSardegnaItinerariCommand: error updating ec_tracks color: '.$e->getMessage());
            $this->error('Error updating database: '.$e->getMessage());

            return Command::FAILURE;
        }

        try {
            $this->logChannel->info(
                'ClearEcTrackColorSardegnaItinerariCommand: cleared color for '.$count.' track(s) (ID: '.implode(', ', $ids).')'
            );
        } catch (\Exception $e) {
            Log::warning('ClearEcTrackColorSardegnaItinerariCommand: log channel write failed: '.$e->getMessage());
        }

        $this->info('Cleared color for '.$count.' Sardegna itinerari track(s) (ID: '.implode(', ', $ids).').');

        return Command::SUCCESS;
    }

    /**
     * @return int|null
     */
    private function resolveAuthorId(string $author)
    {
        if (is_numeric($author)) {
            $user = User::find((int) $author);

            return $user ? $user->id : null;
        }

        $user = User::where('email', strtolower($author))->first();

        return $user ? $user->id : null;
    }
}
