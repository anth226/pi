<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SentData extends Model
{
	protected $fillable = [
		'customer_id',
		'service_name',
		'field',
		'value'
	];
}
