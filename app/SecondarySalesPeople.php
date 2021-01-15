<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SecondarySalesPeople extends Model
{
	protected $fillable = [
		'invoice_id',
		'salespeople_id',
		'sp_type', // 0 -secondary, 1 - primary (for invoice)
		'earnings',
		'percentage',
		'level_id',
		'status' // 0 - commissions not paid, 1 - commissions paid
	];

	public function salespersone()
	{
		return $this->hasOne('App\Salespeople', 'id','salespeople_id')->withTrashed();
	}

	public function level(){
		return $this->hasOne('App\SalespeopleLevels', 'id','level_id')->latest();
	}

	public function level2(){
		return $this->hasOne('App\SalespeoplePecentageLog', 'salespeople_id','salespeople_id')->latest();
	}

}
