<?php

namespace App\Console\Commands;

use App\Models\EcTrack;
use Illuminate\Console\Command;

class EcTrackIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geohub:index-tracks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $a = EcTrack::all();
        foreach($a as $t)
        {
            $t->updated_at=date('Y-m-d h:i:s');$t->save();
        }
        return 0;
    }
}
