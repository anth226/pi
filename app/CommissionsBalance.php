<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommissionsBalance extends Model
{
	protected $fillable = [
		'salespeople_id',
		'amount'
	];
}
