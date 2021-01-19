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
		'formated_phone_number',
		'pipedrive_user_id'
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
		return $this->hasMany('App\Invoices', 'salespeople_id','id');
	}


	public function level(){
		return $this->hasOne('App\SalespeoplePecentageLog', 'salespeople_id','id')->latest();
	}

	public function level2(){
		return $this->hasOne('App\SalespeoplePecentageLog', 'id','salespeople_id')->latest();
	}
}
