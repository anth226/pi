<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalespeopleLevelsUpdates extends Model
{
	protected $fillable = [
		'salespeople_id',
		'created_at',
		'updated_at'
	];
}
