<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SentData extends Model
{
	protected $fillable = [
		'customer_id',
		'service_type', // 1 -stripe, 2 - firebase, 3 - klaviyo, 4 - sms_system, 5 - pipedrive
		'field',
		'value',
		'action' //0 - create, 1-delete/unsubscribe/cancel
	];

	public const SERVICES = [
		1 => 'Stripe',
		2 => 'Firebase',
		3 => 'Klaviyo',
		4 => 'SMS System',
		5 => 'Pipedrive',
	];
}
