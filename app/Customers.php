<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customers extends Model
{
	use Sortable;
	use SoftDeletes;

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
		'stripe_customer_id',
		'rep_payment_status' // 1 - 'Paid Sales Rep', 2 - 'Refund'
	];

	public static function getIdsAndFullNames(){
		return self::selectRaw("id, CONCAT(first_name, ' ', last_name) as full_name")
			->orderBy('id', 'desc')
			->limit(10000)
			->pluck('full_name', 'id')
			->toArray()
		;
	}

	public function invoices()	{
		return $this->hasOne('App\Invoices', 'customer_id','id');
	}
}
