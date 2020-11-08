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
		'state_id',
		'email',
		'password',
		'phone_number',
		'cc'
	];
}
