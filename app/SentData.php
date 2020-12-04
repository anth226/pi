<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SentData extends Model
{
	protected $fillable = [
		'customer_id',
		'service_type', // 1 -stripe, 2 - firebase, 3 - klaviyo, 4 - sms_system
		'field',
		'value'
	];
}
