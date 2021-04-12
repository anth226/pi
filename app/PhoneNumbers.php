<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneNumbers extends Model
{
	use SoftDeletes;

	protected $fillable = [
		'isActive',
		'friendlyName',
		'phoneNumber',
		'sid',
		'buyer_user_id',
		'owner_user_id'
	];
}
