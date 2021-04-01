<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneNumbers extends Model
{
	use SoftDeletes;

	protected $fillable = [
		'phone_number',
		'timezone'
	];
}
