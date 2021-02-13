<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceGenerator extends Model
{
	protected $fillable = [
		'first_name',
		'last_name',
		'address_1',
		'address_2',
		'city',
		'zip',
		'state',
		'email',
		'phone_number',
		'formated_phone_number',
		'access_date',
		'cc',
		'sales_price',
		'grand_total',
		'paid',
		'own',
		'discount_total',
		'discounts',
		'invoice_number',
	];

	protected $casts = [
		'discounts' => 'array'
	];
}
