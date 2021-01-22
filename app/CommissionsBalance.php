<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommissionsBalance extends Model
{
	protected $fillable = [
		'salespeople_id',
		'paid_amount',
		'unpaid_balance'
	];

	public function salespersone()
	{
		return $this->hasOne('App\Salespeople', 'id','salespeople_id')->withTrashed();
	}
}
