<?php

namespace App\Console\Commands;

use App\Jobs\UpdateCurrentDataJob;
use App\Models\User;
use App\Traits\HandlesData;
use App\Models\EcTrack;
use App\Jobs\UpdateEcTrack3DDemJob;
use App\Jobs\UpdateEcTrackDemJob;
use App\Jobs\UpdateManualDataJob;
use App\Mail\UpdateTrackFromOsmEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

class UpdateTrackFromOsm extends Command
{
    use HandlesData;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:update_track_from_osm {user_email} {emails?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loops through all the tracks belonging to the user identified by user_email. If the parameter osmid is not null, it performs some sync operations from OSM to GEOHUB.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userEmail = $this->argument('user_email');
        if ($userEmail == null) {
            $this->error('Please provide a user email');
            return 0;
        }

        $user = User::where('email', $userEmail)->first();
        if (!$user) {
            $this->error('User not found');
            return 0;
        }
        $tracks = $user->ecTracks()->whereNotNull('osmid')->get();
        $mailErrors = [];

        $this->info('Updating tracks(' . count($tracks) . ') for user ' . $user->name . ' (' . $user->email . ')' . '...');

        //loop over all the tracks and check if the osmid is not null
        foreach ($tracks as $track) {
            $result = $this->updateOsmData($track);
            if (!$result['success']) {
                $this->error($track->id . ' UpdateTrackFromOsm FAILED: ' . $track->name . ' (' . $track->osmid . '): ' . $result['message']);
                $mailErrors[] = $this->formatErrorMessage($track, $result['message']);
            } else {
                $this->info($track->id . ' UpdateTrackFromOsm SUCCESS: ' . $track->name . ' (' . $track->osmid . ')');
                $chain = [];
                $chain[] = new UpdateEcTrackDemJob($track);
                $chain[] = new UpdateManualDataJob($track);
                $chain[] = new UpdateCurrentDataJob($track);
                $chain[] = new UpdateEcTrack3DDemJob($track);
                Bus::chain($chain)->dispatch();
            }
        }
        $this->info('Tracks for user ' . $user->name . ' (' . $user->email . ')' . ' updated!');
        $emails = $this->argument('emails');
        if (!empty($emails) && !empty($mailErrors)) {
            $emailList = explode(',', $emails);
            foreach ($emailList as $email) {
                Mail::to(trim($email))->send(new UpdateTrackFromOsmEmail($mailErrors));
            }
        }
    }

    private function formatErrorMessage(EcTrack $track, $errorMessage = '')
    {
        return 'Track ' . $track->name . ' ("geohub:https://geohub.webmapp.it/resources/ec-tracks/' . $track->id . '") "osm:https://www.openstreetmap.org/relation/' . $track->osmid . '") ' . $errorMessage;
    }

    // $s =json_decode(App\Http\Facades\OsmClient::getGeojson('relation/8370439'), true)
}
