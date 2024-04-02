<?php

namespace App\Jobs;

use App\Services\PBFGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePBFJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $z;
    protected $x;
    protected $y;
    protected $app_id;
    protected $author_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($z, $x, $y, $app_id, $author_id)
    {
        $this->z = $z;
        $this->x = $x;
        $this->y = $y;
        $this->app_id = $app_id;
        $this->author_id = $author_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $generator = new PBFGenerator($this->app_id,$this->author_id);
        $generator->generate($this->z, $this->x, $this->y);
    }
}
