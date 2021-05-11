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
		'cc',
		'cc_number',
		'paid',
		'own',
		'paid_at',
		'refunded_at',
		'pdftemplate_id',
		'status',
		'stripe_subscription_id',
		'stripe_customer_id',
		'stripe_current_period_end',
		'stripe_current_period_start',
		'stripe_subscription_status'
	];

	public const STATUS = [
		1 => 'Active',
		2 => 'High Risk',
		3 => 'Refunded'
	];
	public const SUBSCRIPTION_SUBS_STATUS = [
		1 => 'Active',
		6 => 'Past Due',
		7 => 'Unpaid',
		8 => 'Canceled',
		9 => 'Incomplete',
		10 => 'Incomplete Expired',
		11 => 'Trialing'
	];

	public const STRIPE_STATUSES = [
		'active' =>1,
		'past_due' => 6,
		'unpaid' => 7,
		'canceled' => 8,
		'incomplete' => 9,
		'incomplete_expired' => 10,
		'trialing' => 11
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

	public function commissionPayments()
	{
		return $this->hasMany('App\CommissionPaymentsLog', 'invoice_id');
	}

	public function pdftemplate()
	{
		return $this->hasOne('App\PdfTemplates', 'id','pdftemplate_id')->withTrashed();
	}

	public function supportReps()
	{
		return $this->hasMany('App\InvoiceSupport', 'invoice_id');
	}

	public function supportTodo()
	{
		return $this->hasMany('App\SupportTodo', 'invoice_id')->orderBy('task_status')->orderBy('created_at', 'desc');
	}

	public function supportTodoActive()
	{
		return $this->hasMany('App\SupportTodo', 'invoice_id')->where('task_status', 1);
	}

	public function supportTodoCompleted()
	{
		return $this->hasMany('App\SupportTodo', 'invoice_id')->where('task_status', 2);
	}
}
