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
		'phone_number'
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
		return $this->belongsTo('App\Invoices', 'customer_id','id');
	}
}
