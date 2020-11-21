<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailForQueuing extends Mailable
{
    use Queueable, SerializesModels;

    protected $name, $email, $salesperson;

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

	public function setSalesperson($salesperson){
		$this->salesperson = $salesperson;
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
