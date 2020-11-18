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

	public function template()
	{
		return $this->hasOne('App\EmailTemplates', 'id','email_template_id');
	}

	public function customer()
	{
		return $this->hasOne('App\Customers', 'id','customer_id')->withTrashed();
	}

	public function salespersone()
	{
		return $this->hasOne('App\Salespeople', 'id','salespeople_id')->withTrashed();
	}

	public function product()
	{
		return $this->hasOne('App\Products', 'id','product_id')->withTrashed();
	}
}
