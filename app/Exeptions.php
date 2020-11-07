<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Exeptions extends Model
{

	protected $table = 'exeptions';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'error',
		'controller',
		'function'
	];


}
