<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActionsLog extends Model
{
	protected $fillable = [
		'user_id',
		'model',
		'action',
		'related_id',
		'field_name',
		'old_value',
		'new_value'
	];


	public const MODEL = [
		0 => 'NoModel',
		1 => 'Invoices',
		2 => 'Customers',
		3 => 'Salespeople',
		4 => 'Users',
		5 => 'InvoiceGenerator',
		6 => 'EmailTemplates',
		7 => 'Role'
	];

	public const ACTION = [
		0 => 'Created',
		1 => 'Updated',
		2 => 'Deleted',
		3 => 'EmailSent'
	];
}
