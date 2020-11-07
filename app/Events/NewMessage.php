<?php

namespace App\Events;

use App\SupportMessages;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewMessage implements ShouldBroadcast
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public $message;
	public $support_id;

	/**
	 * Create a new event instance.
	 *
	 * @return void
	 */
	public function __construct(SupportMessages $message, $support_id)
	{
		$this->message = $message;
		$this->support_id = $support_id;
	}

	/**
	 * Get the channels the event should broadcast on.
	 *
	 * @return \Illuminate\Broadcasting\Channel|array
	 */
	public function broadcastOn()
	{
		return new PrivateChannel('messages.' . $this->support_id);
	}

	public function broadcastWith()
	{
		$this->message->load('fromContact')->load('fromMMS');

		return ["message" => $this->message];
	}
}
