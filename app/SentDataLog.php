<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SentDataLog extends Model
{
	protected $fillable = [
		'customer_id',
		'lead_id'
	];

}
