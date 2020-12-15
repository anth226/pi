<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SecondarySalesPeople extends Model
{
	protected $fillable = [
		'invoice_id',
		'salespeople_id',
		'sp_type' // 0 -secondary, 1 - primary (for invoice)
	];

	public function salespersone()
	{
		return $this->hasOne('App\Salespeople', 'id','salespeople_id')->withTrashed();
	}

}
