<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PiPersons extends Model
{
	protected $fillable = [
		'ownerId',
		'personId',
		'name',
		'timezone',
		'addTime',
		'label',
		'extra_field',
		'source_field',
		'phone',
		'email',
		'persons_data'
	];
}
