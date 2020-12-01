<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StripeData extends Model
{
	protected $fillable = [
		'customer_id',
		'invoice_id',
		'stripe_customer_id',
		'stripe_subs_id'
	];
}
