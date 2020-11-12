<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoicesLog extends Model
{
	protected $fillable = [
		'action', // 1 - generated email with attach, 2 - email sent
		'user_id',
		'invoice_id'
	];
}
