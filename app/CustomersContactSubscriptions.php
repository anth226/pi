<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomersContactSubscriptions extends Model
{
	use SoftDeletes;

	protected $fillable = [
		'customers_contact_id',
		'user_id',
		'invoice_id',
		'subscription_type',
		'subscription_status'
	];

	public const SUBSCRIPTION_STATUS = [
		0 => 'Not Active',
		1 => 'Active',
		2 => 'Unreachable',
		3 => 'Does not exist',
		4 => 'Duplicate',
		5 => 'Not Valid',
		// for stripe subs status
		6 => 'Past Due',
		7 => 'Unpaid',
		8 => 'Canceled',
		9 => 'Incomplete',
		10 => 'Incomplete Expired',
		11 => 'Trialing',
		// for firebase
		12 => 'Disabled'
	];

	public const SUBSCRIPTION_TYPES = [
		0 => 'Firebase',
		1 => 'Firebase User',
		2 => 'Klaviyo: Daily Prime',
		3 => 'SMS System: Prime sms',
		4 => 'SMS System: Prime email',
		5 => 'Stripe: User',
		6 => 'Stripe: Lifetime',
		7 => 'Stripe: Annual',
		8 => 'Stripe: Daily'
	];

	public function user()
	{
		return $this->hasOne('App\User', 'id','user_id')->withTrashed();
	}

	public function contact()
	{
		return $this->hasOne('App\CustomersContacts', 'id','customers_contact_id')->withTrashed();
	}
}
