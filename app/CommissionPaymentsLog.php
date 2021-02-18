<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommissionPaymentsLog extends Model
{
	protected $fillable = [
		'user_id',
		'invoice_id',
		'salespeople_id',
		'paid_amount',
		'payment_type'  // 0 - regular , 1 - discrepancy, 2 - unpaid
	];
}
