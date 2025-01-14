<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendImportErrorsEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $error_not_created;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($error_not_created)
    {
        $this->error_not_created = $error_not_created;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $error_not_created = $this->error_not_created;

        return $this->from('noreply@webmapp.it', 'Tracks not imported')
            ->subject('Geohub - Sardegna Sentieri - Tracks not imported')
            ->view('mails.tracks.error');
    }
}
