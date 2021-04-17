<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTodo extends Model
{
	use SoftDeletes;

	protected $fillable = [
		'invoice_id',
		'done_by_user_id',
		'added_by_user_id',
		'task_type',
		'task_status',
		'done_at'
	];

	public const TASK_TYPE = [
		0 => 'Source',
		1 => 'EXTRA FIELD'
	];

	public const TASK_STATUS = [
		0 => 'disabled',
		1 => 'active',
		2 => 'done'
	];
}
