<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UpdateTrackFromOsmEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $errorLogs;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($errorLogs)
    {
        $this->errorLogs = $errorLogs;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('noreply@webmapp.it', 'Tracks not imported')
            ->subject('Geohub - Tracks not imported')
            ->view('mails.tracks.UpdateTrackFromOsmEmail')
            ->with(['errorLogs' => $this->errorLogs]);
    }
}
