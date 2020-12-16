<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalespeopleReport extends Model
{
	protected $fillable = [
		'report_date',
		'salespeople_id',
		'percentage',
		'total_sales',
		'sales',
		'earnings'
	];
}
