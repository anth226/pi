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
		'support_rep_user_id',
		'task_type',
		'task_status',
		'done_at'
	];

	public const TASK_TYPE = [
		0 => 'Demo Needed',
		1 => 'Follow up Call',
		2 => 'Refund Requested'
	];

	public const TASK_STATUS = [
		0 => 'disabled',
		1 => 'active',
		2 => 'completed'
	];

	public function addedByuser()
	{
		return $this->hasOne('App\User', 'id','added_by_user_id')->withTrashed();
	}

	public function doneByuser()
	{
		return $this->hasOne('App\User', 'id','done_by_user_id')->withTrashed();
	}

	public function supportRep()
	{
		return $this->hasOne('App\User', 'id','support_rep_user_id')->withTrashed();
	}

	public function invoice()
	{
		return $this->hasOne('App\Invoices', 'id','invoice_id');
	}
}
