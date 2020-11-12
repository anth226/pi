<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoices extends Model
{
	use Sortable;
	use SoftDeletes;

	protected $fillable = [
		'email_template_id',
		'customer_id',
		'salespeople_id',
		'product_id',
		'sales_price',
		'qty',
		'access_date',
		'password',
		'invoice_number',
		'cc'
	];
}
