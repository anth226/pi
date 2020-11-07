<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailForQueuing extends Mailable
{
    use Queueable, SerializesModels;

    protected $name, $email, $unsubscribe_url, $pixel, $tracking, $tracking2, $token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($template, $subject,$from_address, $from_name)
    {
        $this->subject = $subject;
	    $this->from($from_address, $from_name);
        $this->view = $template;
    }

    public function setName($name){
	    $this->name = $name;
    }

	public function setEmail($email){
		$this->email = $email;
	}

	public function setPixel($pixel){
		$this->pixel = $pixel;
	}

	public function setUnsubscribeUrl($unsubscribe_url){
		$this->unsubscribe_url = $unsubscribe_url;
	}

	public function setTracking($tracking){
		$this->tracking = $tracking;
	}

	public function setTracking2($tracking2){
		$this->tracking2 = $tracking2;
	}

	public function setToken($token){
		$this->token = $token;
	}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
	    return $this;
    }
}
