<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceSupport extends Model
{
	protected $fillable = [
		'invoice_id',
		'user_id',
	];

	public function user()
	{
		return $this->hasOne('App\User', 'id','user_id')->withTrashed();
	}
}
