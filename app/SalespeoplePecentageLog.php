<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalespeoplePecentageLog extends Model
{
	protected $fillable = [
		'level_id',
		'salespeople_id',
		'percentage',
		'update_id'
	];

	public function level(){
		return $this->hasOne('App\SalespeopleLevels', 'id','level_id');
	}

}
