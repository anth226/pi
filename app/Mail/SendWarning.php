<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWarning extends Mailable
{
    use Queueable, SerializesModels;
	public $campaign_id;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($campaign_id)
    {
	    $this->campaign_id = $campaign_id;
	    $this->subject = 'Warning';
	    $this->from('admin@magicstarsystem.com', 'Magicstarsystem Warning');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.warning');
    }
}
