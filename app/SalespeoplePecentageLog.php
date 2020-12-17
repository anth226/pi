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

	public function level(){
		return $this->hasOne('App\SalespeopleLevels', 'id','level_id');
	}

//	public function salespeople()	{
//		return $this->belongsTo('App\Salespeople', 'salespeople_id','id');
//	}
}
