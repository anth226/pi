<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceGenerator extends Model
{
	protected $fillable = [
		'first_name',
		'last_name',
		'email',
		'access_date',
		'invoice_number',
		'invoice_data',
		'own',
	];

	protected $casts = [
		'invoice_data' => 'array'
	];
}
