<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salespeople extends Model
{
	use Sortable;
	use SoftDeletes;

	protected $fillable = [
		'first_name',
		'last_name',
		'name_for_invoice',
		'email',
		'phone_number',
		'formated_phone_number'
	];

	public static function getIdsAndFullNames(){
		return self::selectRaw("id, CONCAT(name_for_invoice, ' (', first_name, ' ', last_name, ')') as full_name")
		           ->orderBy('id', 'desc')
		           ->limit(1000)
		           ->pluck('full_name', 'id')
		           ->toArray()
			;
	}

	public function invoices()	{
		return $this->belongsTo('App\Invoices', 'salespeople_id','id');
	}
}
