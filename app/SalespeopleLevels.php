<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalespeopleLevels extends Model
{
	protected $fillable = [
		'title',
		'percentage'
	];

	public function salespeople()	{
		return $this->hasMany('App\Salespeople', 'level_id','id');
	}
}
