<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoicesLog extends Model
{
	protected $fillable = [
		'action', // 1 - refund
		'invoice_id',
		'user_id',
		'service_id',
		'result',
		'error',
		'message'
	];

	public const ACTION = [
		1 => 'Refund'
	];

	public const SERVICES = [
		0 => 'PipeDrive',
		1 => 'FireBase Acc',
		2 => 'FireBase User',
		3 => 'Stripe Subs',
		4 => 'Stripe User',
		5 => 'Klaviyo',
		6 => 'SMS System',
	];
}
