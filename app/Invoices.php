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
		'customer_id',
		'salespeople_id',
		'product_id',
		'sales_price',
		'qty',
		'access_date',
		'invoice_number',
		'cc'
	];

	public function customer()
	{
		return $this->hasOne('App\Customers', 'id','customer_id')->withTrashed();
	}

	public function salespersone()
	{
		return $this->hasOne('App\Salespeople', 'id','salespeople_id')->withTrashed();
	}

	public function salespeople()
	{
		return $this->hasMany('App\SecondarySalesPeople', 'invoice_id');
	}

	public function product()
	{
		return $this->hasOne('App\Products', 'id','product_id')->withTrashed();
	}

	public function emails()
	{
		return $this->hasMany('App\EmailLogs', 'id','invoice_id');
	}
}
