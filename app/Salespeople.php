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
}
