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

	public const SERVICES_OLD = [
		0 => 'PipeDrive',
		1 => 'FireBase Acc',
		2 => 'FireBase User',
		3 => 'Stripe Subs',
		4 => 'Stripe User',
		5 => 'Klaviyo',
		6 => 'SMS System',
	];

	public const SERVICES = [
		1 => 'Stripe',
		2 => 'Firebase',
		3 => 'Klaviyo',
		4 => 'SMS System',
		5 => 'Pipedrive',
		6 => 'Stripe Subs',
		7 => 'FireBase User',
		8 => 'Klaviyo User',
	];
}
