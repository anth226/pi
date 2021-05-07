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
		7 => 'Role',
		8 => 'Contact'
	];

	public const ACTION = [
		0 => 'Created',
		1 => 'Updated',
		2 => 'Deleted',
		3 => 'Email Sent',
		4 => 'Task Added',
		5 => 'Task Removed',
		6 => 'Task Completed',
		7 => 'Contact Subscribed to Klavio',
		8 => 'Contact Unsubscribed from Klaviyo',
		9 => 'Contact Subscribed to SmsSystem',
		10 => 'Contact Unsubscribed from SmsSystem'
	];
}
