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

	public function salespeoplepercentagelog()	{
		return $this->hasMany('App\SalespeoplePecentageLog', 'level_id','id');
	}

	public static function getIdsAndFullNames(){
		return self::selectRaw("id, CONCAT(title, ' | ', percentage) as full_name")
		           ->orderBy('id', 'desc')
		           ->limit(1000)
		           ->pluck('full_name', 'id')
		           ->toArray()
			;
	}
}
