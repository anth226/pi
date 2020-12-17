<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalespeoplePecentageLog extends Model
{
	protected $fillable = [
		'level_id',
		'salespeople_id',
		'percentage'
	];
}
