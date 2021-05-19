<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customers extends Model
{
	use Sortable;
	use SoftDeletes;

	protected $guarded = ['id'];

	public static function getIdsAndFullNames(){
		return self::selectRaw("id, CONCAT(first_name, ' ', last_name) as full_name")
			->orderBy('id', 'desc')
			->limit(10000)
			->pluck('full_name', 'id')
			->toArray()
		;
	}

	public function invoices()	{
		return $this->hasMany('App\Invoices', 'customer_id','id');
	}

	public function pipedriveData()	{
		return $this->hasMany('App\PipedriveData', 'customer_id','id');
	}
	public function pipedriveSources()	{
		return $this->hasMany('App\PipedriveData', 'customer_id','id')->where('field_name',0);
	}
	public function pipedriveExtra()	{
		return $this->hasMany('App\PipedriveData', 'customer_id','id')->where('field_name',1);
	}
	public function contacts()
	{
		return $this->hasMany('App\CustomersContacts', 'customer_id', 'id');
	}
}
