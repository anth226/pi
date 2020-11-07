<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Errors extends Model
{
	protected $fillable = [
		'error',
		'controller',
		'function'
	];
}
