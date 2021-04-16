<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceSupport extends Model
{
	protected $fillable = [
		'invoice_id',
		'user_id',
	];
}
