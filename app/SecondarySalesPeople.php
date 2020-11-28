<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SecondarySalesPeople extends Model
{
	protected $fillable = [
		'invoice_id',
		'salespeople_id'
	];

	public function salespersone()
	{
		return $this->hasOne('App\Salespeople', 'id','salespeople_id')->withTrashed();
	}

}
